<?php

namespace App\Providers;

use App\Events\BillDueReminder;
use App\Events\BudgetThresholdReached;
use App\Events\SavingsGoalCompleted;
use App\Events\TransactionCreated;
use App\Events\ZakatDueReminder;
use App\Listeners\SendBillDueNotification;
use App\Listeners\SendBudgetAlertNotification;
use App\Listeners\SendSavingsGoalNotification;
use App\Listeners\SendTransactionApprovalNotification;
use App\Listeners\SendTransactionCreatedNotification;
use App\Listeners\SendZakatDueNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TransactionCreated::class => [
            SendTransactionCreatedNotification::class,
            SendTransactionApprovalNotification::class,
        ],
        BudgetThresholdReached::class => [
            SendBudgetAlertNotification::class,
        ],
        BillDueReminder::class => [
            SendBillDueNotification::class,
        ],
        ZakatDueReminder::class => [
            SendZakatDueNotification::class,
        ],
        SavingsGoalCompleted::class => [
            SendSavingsGoalNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
