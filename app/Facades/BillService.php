<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Bill createBill(\App\Models\Family $family, array $data)
 * @method static \App\Models\Bill updateBill(\App\Models\Bill $bill, array $data)
 * @method static void deleteBill(\App\Models\Bill $bill)
 * @method static void markAsPaid(\App\Models\Bill $bill, ?int $transactionId = null)
 * @method static array getUpcomingBills(\App\Models\Family $family, int $days = 7)
 * @method static array getOverdueBills(\App\Models\Family $family)
 * @method static float estimateNextBill(\App\Models\Bill $bill)
 * @method static void sendBillReminders()
 * @method static void checkOverdueBills()
 * @method static array splitBillAmount(\App\Models\Bill $bill)
 * @method static array getBillStatistics(\App\Models\Family $family)
 *
 * @see \App\Services\BillService
 */
class BillService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\BillService::class;
    }
}