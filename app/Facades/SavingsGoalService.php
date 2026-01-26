<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\SavingsGoal createGoal(\App\Models\Family $family, array $data)
 * @method static \App\Models\SavingsGoal updateGoal(\App\Models\SavingsGoal $goal, array $data)
 * @method static void deleteGoal(\App\Models\SavingsGoal $goal)
 * @method static void contribute(\App\Models\SavingsGoal $goal, float $amount, ?int $transactionId = null)
 * @method static array getGoalProgress(\App\Models\SavingsGoal $goal)
 * @method static array getFamilyGoalsOverview(\App\Models\Family $family)
 * @method static void processAutoContributions()
 *
 * @see \App\Services\SavingsGoalService
 */
class SavingsGoalService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\SavingsGoalService::class;
    }
}