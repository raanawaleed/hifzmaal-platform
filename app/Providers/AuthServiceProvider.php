<?php

namespace App\Providers;

use App\Models\Bill;
use App\Models\Budget;
use App\Models\Family;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Policies\BillPolicy;
use App\Policies\BudgetPolicy;
use App\Policies\FamilyPolicy;
use App\Policies\SavingsGoalPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Family::class => FamilyPolicy::class,
        Transaction::class => TransactionPolicy::class,
        Budget::class => BudgetPolicy::class,
        Bill::class => BillPolicy::class,
        SavingsGoal::class => SavingsGoalPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}