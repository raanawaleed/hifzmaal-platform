<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Family;
use Carbon\Carbon;

class BudgetService
{
    public function createBudget(Family $family, array $data): Budget
    {
        return $family->budgets()->create($data);
    }

    public function updateBudget(Budget $budget, array $data): Budget
    {
        $budget->update($data);
        return $budget->fresh();
    }

    public function deleteBudget(Budget $budget): void
    {
        $budget->delete();
    }

    public function getBudgetStatus(Budget $budget): array
    {
        $spent = $budget->getSpentAmount();
        $percentage = $budget->getPercentageUsed();
        $remaining = $budget->getRemainingAmount();

        return [
            'budget_id' => $budget->id,
            'name' => $budget->name,
            'category' => $budget->category->name,
            'category_color' => $budget->category->color,
            'amount' => (float) $budget->amount,
            'spent' => (float) $spent,
            'remaining' => (float) $remaining,
            'percentage' => round($percentage, 2),
            'is_over_budget' => $budget->isOverBudget(),
            'should_alert' => $budget->shouldAlert(),
            'status' => $this->getBudgetStatusLabel($percentage),
            'days_remaining' => $budget->getDaysRemaining(),
        ];
    }

    protected function getBudgetStatusLabel(float $percentage): string
    {
        return match(true) {
            $percentage >= 100 => 'exceeded',
            $percentage >= 80 => 'warning',
            $percentage >= 50 => 'on_track',
            default => 'safe',
        };
    }

    public function getFamilyBudgetOverview(Family $family): array
    {
        $budgets = $family->budgets()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('category')
            ->get();

        $totalBudget = $budgets->sum('amount');
        $totalSpent = $budgets->sum(fn($budget) => $budget->getSpentAmount());

        return [
            'total_budget' => (float) $totalBudget,
            'total_spent' => (float) $totalSpent,
            'total_remaining' => (float) max(0, $totalBudget - $totalSpent),
            'overall_percentage' => $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 2) : 0,
            'budgets_count' => $budgets->count(),
            'over_budget_count' => $budgets->filter(fn($b) => $b->isOverBudget())->count(),
            'budgets' => $budgets->map(fn($budget) => $this->getBudgetStatus($budget))->toArray(),
        ];
    }

    public function createMonthlyBudget(Family $family, int $categoryId, float $amount): Budget
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        return $this->createBudget($family, [
            'category_id' => $categoryId,
            'name' => 'Monthly Budget - ' . now()->format('F Y'),
            'amount' => $amount,
            'period' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'alert_threshold' => 80,
            'is_active' => true,
        ]);
    }

    public function createYearlyBudget(Family $family, int $categoryId, float $amount): Budget
    {
        $startDate = now()->startOfYear();
        $endDate = now()->endOfYear();

        return $this->createBudget($family, [
            'category_id' => $categoryId,
            'name' => 'Yearly Budget - ' . now()->format('Y'),
            'amount' => $amount,
            'period' => 'yearly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'alert_threshold' => 80,
            'is_active' => true,
        ]);
    }
}