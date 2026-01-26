<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'transactions:process-recurring';

    protected $description = 'Process and create recurring transactions';

    public function handle(TransactionService $transactionService): int
    {
        $this->info('Processing recurring transactions...');

        $recurringTransactions = Transaction::where('is_recurring', true)
            ->where('status', 'approved')
            ->whereNull('parent_transaction_id')
            ->get();

        $created = 0;

        foreach ($recurringTransactions as $transaction) {
            try {
                $nextDate = $this->calculateNextDate($transaction);

                if (!$nextDate || ($transaction->recurring_end_date && $nextDate->isAfter($transaction->recurring_end_date))) {
                    continue;
                }

                // Check if already created for this date
                $exists = Transaction::where('parent_transaction_id', $transaction->id)
                    ->where('date', $nextDate)
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Create new transaction instance
                $newTransaction = $transaction->replicate();
                $newTransaction->parent_transaction_id = $transaction->id;
                $newTransaction->date = $nextDate;
                $newTransaction->status = 'approved';
                $newTransaction->created_at = now();
                $newTransaction->updated_at = now();
                $newTransaction->save();

                // Update account balance
                $this->updateAccountBalance($newTransaction);

                $created++;
                $this->info("Created recurring transaction: {$transaction->description} for {$nextDate->format('Y-m-d')}");
            } catch (\Exception $e) {
                $this->error("Failed to process transaction {$transaction->id}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$created} recurring transactions.");

        return self::SUCCESS;
    }

    protected function calculateNextDate(Transaction $transaction): ?Carbon
    {
        $lastDate = Transaction::where('parent_transaction_id', $transaction->id)
            ->orderBy('date', 'desc')
            ->value('date');

        $baseDate = $lastDate ? Carbon::parse($lastDate) : Carbon::parse($transaction->date);

        return match ($transaction->recurring_frequency) {
            'daily' => $baseDate->addDay(),
            'weekly' => $baseDate->addWeek(),
            'monthly' => $baseDate->addMonth(),
            'yearly' => $baseDate->addYear(),
            default => null,
        };
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
}
