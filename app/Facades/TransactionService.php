<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Transaction createTransaction(\App\Models\Family $family, array $data)
 * @method static \App\Models\Transaction updateTransaction(\App\Models\Transaction $transaction, array $data)
 * @method static void approveTransaction(\App\Models\Transaction $transaction)
 * @method static void rejectTransaction(\App\Models\Transaction $transaction)
 * @method static void deleteTransaction(\App\Models\Transaction $transaction)
 * @method static array getCategoryWiseExpenses(\App\Models\Family $family, int $month, int $year)
 * @method static array getMonthlyTrend(\App\Models\Family $family, int $months = 6)
 * @method static array getRecentTransactions(\App\Models\Family $family, int $limit = 10)
 *
 * @see \App\Services\TransactionService
 */
class TransactionService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\TransactionService::class;
    }
}