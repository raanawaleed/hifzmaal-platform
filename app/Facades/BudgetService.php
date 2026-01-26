<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Budget createBudget(\App\Models\Family $family, array $data)
 * @method static \App\Models\Budget updateBudget(\App\Models\Budget $budget, array $data)
 * @method static void deleteBudget(\App\Models\Budget $budget)
 * @method static array getBudgetStatus(\App\Models\Budget $budget)
 * @method static array getFamilyBudgetOverview(\App\Models\Family $family)
 * @method static \App\Models\Budget createMonthlyBudget(\App\Models\Family $family, int $categoryId, float $amount)
 * @method static \App\Models\Budget createYearlyBudget(\App\Models\Family $family, int $categoryId, float $amount)
 *
 * @see \App\Services\BudgetService
 */
class BudgetService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\BudgetService::class;
    }
}