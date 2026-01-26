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
            // Validate account belongs to family
            $account = $family->accounts()->findOrFail($data['account_id']);
            
            // Check balance for expense
            if ($data['type'] === 'expense' && $account->balance < $data['amount']) {
                throw new InsufficientBalanceException("Insufficient balance in {$account->name}");
            }

            // Create transaction
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
                'transfer_to_account_id' => $data['transfer_to_account_id'] ?? null,
                'is_recurring' => $data['is_recurring'] ?? false,
                'recurring_frequency' => $data['recurring_frequency'] ?? null,
                'recurring_end_date' => $data['recurring_end_date'] ?? null,
                'needs_approval' => $this->needsApproval($family, $data),
                'status' => $this->needsApproval($family, $data) ? 'pending' : 'approved',
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
            $oldAmount = $transaction->amount;
            $oldType = $transaction->type;
            $oldAccountId = $transaction->account_id;

            // Revert old balance update if was approved
            if ($transaction->status === 'approved') {
                $this->revertAccountBalance($transaction);
            }

            $transaction->update(array_filter([
                'account_id' => $data['account_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'type' => $data['type'] ?? null,
                'amount' => $data['amount'] ?? null,
                'date' => $data['date'] ?? null,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]));

            // Update balance with new values if approved
            if ($transaction->status === 'approved') {
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