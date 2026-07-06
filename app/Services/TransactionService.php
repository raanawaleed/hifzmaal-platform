<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Family;
use App\Events\TransactionCreated;
use App\Events\TransactionApproved;
use App\Events\BudgetThresholdReached;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransactionException;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function createTransaction(Family $family, array $data): Transaction
    {
        return DB::transaction(function () use ($family, $data) {
            // Lock the account row so concurrent requests cannot both pass
            // the balance check (double-spend race).
            $account = $family->accounts()
                ->whereKey($data['account_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $transferTo = null;
            if ($data['type'] === 'transfer') {
                if (empty($data['transfer_to_account_id'])) {
                    throw new InvalidTransactionException('A transfer requires a destination account.');
                }

                if ((int) $data['transfer_to_account_id'] === (int) $data['account_id']) {
                    throw new InvalidTransactionException('Cannot transfer to the same account.');
                }

                $transferTo = $family->accounts()
                    ->whereKey($data['transfer_to_account_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // Expenses and transfers both draw the balance down.
            if (in_array($data['type'], ['expense', 'transfer'], true) && $account->balance < $data['amount']) {
                throw new InsufficientBalanceException("Insufficient balance in {$account->name}");
            }

            $needsApproval = $this->needsApproval($family, $data);

            $transaction = $family->transactions()->create([
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'],
                'created_by' => auth()->id(),
                'type' => $data['type'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $family->currency,
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'transfer_to_account_id' => $transferTo?->id,
                'is_recurring' => $data['is_recurring'] ?? false,
                'recurring_frequency' => $data['recurring_frequency'] ?? null,
                'recurring_end_date' => $data['recurring_end_date'] ?? null,
                'needs_approval' => $needsApproval,
                'status' => $needsApproval ? 'pending' : 'approved',
            ]);

            // Handle receipts
            if (isset($data['receipts']) && is_array($data['receipts'])) {
                foreach ($data['receipts'] as $receipt) {
                    $transaction->addMedia($receipt)->toMediaCollection('receipts');
                }
            }

            // Update account balance if approved
            if ($transaction->status === 'approved') {
                $this->updateAccountBalance($transaction);
            }

            // Fire event
            event(new TransactionCreated($transaction));

            // Check budget threshold
            $this->checkBudgetThreshold($transaction);

            return $transaction->fresh();
        });
    }

    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $family = $transaction->family;

            // Only fields present in the payload are updated; array_filter is
            // deliberately avoided so 0, '' and explicit nulls survive.
            $updatable = ['account_id', 'category_id', 'type', 'amount', 'date',
                'description', 'notes', 'transfer_to_account_id'];
            $changes = array_intersect_key($data, array_flip($updatable));

            $newType = $changes['type'] ?? $transaction->type;
            $newAmount = $changes['amount'] ?? $transaction->amount;
            $newAccountId = $changes['account_id'] ?? $transaction->account_id;
            $newTransferToId = array_key_exists('transfer_to_account_id', $changes)
                ? $changes['transfer_to_account_id']
                : $transaction->transfer_to_account_id;

            if ($newType === 'transfer') {
                if (! $newTransferToId) {
                    throw new InvalidTransactionException('A transfer requires a destination account.');
                }
                if ((int) $newTransferToId === (int) $newAccountId) {
                    throw new InvalidTransactionException('Cannot transfer to the same account.');
                }
                $family->accounts()->whereKey($newTransferToId)->firstOrFail();
            } else {
                $changes['transfer_to_account_id'] = null;
            }

            if (isset($changes['account_id'])) {
                $family->accounts()->whereKey($changes['account_id'])->firstOrFail();
            }

            // Lock every account involved (old and new) before touching balances.
            $family->accounts()
                ->whereIn('id', array_filter([
                    $transaction->account_id,
                    $transaction->transfer_to_account_id,
                    $newAccountId,
                    $newTransferToId,
                ]))
                ->lockForUpdate()
                ->get();

            if ($transaction->status === 'approved') {
                $this->revertAccountBalance($transaction);
            }

            $transaction->update($changes);
            $transaction->refresh();

            if ($transaction->status === 'approved') {
                // The reverted balance must still cover the new outflow.
                $account = $family->accounts()->whereKey($transaction->account_id)->first();
                if (in_array($transaction->type, ['expense', 'transfer'], true)
                    && $account->balance < $transaction->amount) {
                    throw new InsufficientBalanceException("Insufficient balance in {$account->name}");
                }

                $this->updateAccountBalance($transaction);
            }

            return $transaction->fresh();
        });
    }

    public function approveTransaction(Transaction $transaction): void
    {
        if ($transaction->status === 'approved') {
            return;
        }

        DB::transaction(function () use ($transaction) {
            // Approving applies the balance move, so the funds must still be
            // there — check under lock, or a pending expense could push the
            // account negative.
            $account = Account::whereKey($transaction->account_id)->lockForUpdate()->first();

            if (in_array($transaction->type, ['expense', 'transfer'], true)
                && $account->balance < $transaction->amount) {
                throw new InsufficientBalanceException("Insufficient balance in {$account->name}");
            }

            $transaction->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->updateAccountBalance($transaction);

            event(new TransactionApproved($transaction));
        });
    }

    public function rejectTransaction(Transaction $transaction): void
    {
        $transaction->update(['status' => 'rejected']);
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            if ($transaction->status === 'approved') {
                Account::whereIn('id', array_filter([
                    $transaction->account_id,
                    $transaction->transfer_to_account_id,
                ]))->lockForUpdate()->get();

                $this->revertAccountBalance($transaction);
            }

            $transaction->delete();
        });
    }

    protected function updateAccountBalance(Transaction $transaction): void
    {
        $account = $transaction->account;

        if ($transaction->type === 'income') {
            $account->increment('balance', $transaction->amount);
        } elseif ($transaction->type === 'expense') {
            $account->decrement('balance', $transaction->amount);
        } elseif ($transaction->type === 'transfer' && $transaction->transferToAccount) {
            $account->decrement('balance', $transaction->amount);
            $transaction->transferToAccount->increment('balance', $transaction->amount);
        }
    }

    protected function revertAccountBalance(Transaction $transaction): void
    {
        $account = $transaction->account;

        if ($transaction->type === 'income') {
            $account->decrement('balance', $transaction->amount);
        } elseif ($transaction->type === 'expense') {
            $account->increment('balance', $transaction->amount);
        } elseif ($transaction->type === 'transfer' && $transaction->transferToAccount) {
            $account->increment('balance', $transaction->amount);
            $transaction->transferToAccount->decrement('balance', $transaction->amount);
        }
    }

    protected function needsApproval(Family $family, array $data): bool
    {
        $member = auth()->user()->familyMemberships()
            ->where('family_id', $family->id)
            ->first();

        if (!$member || !$member->spending_limit || $member->role === 'owner') {
            return false;
        }

        return $data['type'] === 'expense' && $data['amount'] > $member->spending_limit;
    }

    protected function checkBudgetThreshold(Transaction $transaction): void
    {
        if ($transaction->type !== 'expense' || $transaction->status !== 'approved') {
            return;
        }

        $budget = $transaction->category->budgets()
            ->where('family_id', $transaction->family_id)
            ->where('is_active', true)
            ->where('start_date', '<=', $transaction->date)
            ->where('end_date', '>=', $transaction->date)
            ->first();

        if ($budget && $budget->shouldAlert()) {
            event(new BudgetThresholdReached($budget));
        }
    }

    public function getCategoryWiseExpenses(Family $family, int $month, int $year): array
    {
        $expenses = $family->transactions()
            ->where('type', 'expense')
            ->where('status', 'approved')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('category')
            ->get();

        $total = $expenses->sum('amount');

        return $expenses->groupBy('category_id')
            ->map(fn($transactions) => [
                'category' => $transactions->first()->category->name,
                'category_color' => $transactions->first()->category->color,
                'total' => $transactions->sum('amount'),
                'count' => $transactions->count(),
                'percentage' => $total > 0 ? round(($transactions->sum('amount') / $total) * 100, 2) : 0,
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();
    }

    public function getMonthlyTrend(Family $family, int $months = 6): array
    {
        $data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $income = $family->getMonthlyIncome($month, $year);
            $expense = $family->getMonthlyExpense($month, $year);

            $data[] = [
                'month' => $date->format('M Y'),
                'income' => (float) $income,
                'expense' => (float) $expense,
                'net' => (float) ($income - $expense),
            ];
        }

        return $data;
    }

    public function getRecentTransactions(Family $family, int $limit = 10): array
    {
        return $family->transactions()
            ->with(['category', 'account', 'creator'])
            ->where('status', 'approved')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}