<?php

namespace App\Services;

use App\Models\SavingsGoal;
use App\Models\Family;
use App\Events\SavingsGoalCompleted;
use App\Events\SavingsGoalMilestone;
use Illuminate\Support\Facades\DB;

class SavingsGoalService
{
    public function createGoal(Family $family, array $data): SavingsGoal
    {
        $goal = $family->savingsGoals()->create(array_merge($data, [
            'start_date' => $data['start_date'] ?? now(),
            'current_amount' => $data['current_amount'] ?? 0,
        ]));

        return $goal;
    }

    public function updateGoal(SavingsGoal $goal, array $data): SavingsGoal
    {
        $goal->update($data);
        return $goal->fresh();
    }

    public function deleteGoal(SavingsGoal $goal): void
    {
        $goal->delete();
    }

    public function contribute(SavingsGoal $goal, float $amount): void
    {
        DB::transaction(function () use ($goal, $amount) {
            // Lock the goal row so concurrent contributions can't overshoot.
            $locked = SavingsGoal::whereKey($goal->id)->lockForUpdate()->first();

            $previousAmount = (float) $locked->current_amount;

            // Clamp so the goal never exceeds its target.
            $remaining = max(0, (float) $locked->target_amount - $previousAmount);
            $applied = min($amount, $remaining);

            if ($applied <= 0) {
                return;
            }

            $locked->increment('current_amount', $applied);
            $locked->refresh();

            $this->checkMilestones($locked, $previousAmount);

            if ($locked->isCompleted()) {
                event(new SavingsGoalCompleted($locked));
            }

            $goal->refresh();
        });
    }

    protected function checkMilestones(SavingsGoal $goal, float $previousAmount): void
    {
        $milestones = [25, 50, 75, 90];

        $previousPercentage = ($previousAmount / $goal->target_amount) * 100;
        $currentPercentage = $goal->getProgressPercentage();

        foreach ($milestones as $milestone) {
            if ($previousPercentage < $milestone && $currentPercentage >= $milestone) {
                event(new SavingsGoalMilestone($goal, $milestone));
            }
        }
    }

    public function getGoalProgress(SavingsGoal $goal): array
    {
        return [
            'goal_id' => $goal->id,
            'name' => $goal->name,
            'type' => $goal->type,
            'target_amount' => (float) $goal->target_amount,
            'current_amount' => (float) $goal->current_amount,
            'remaining_amount' => (float) $goal->getRemainingAmount(),
            'progress_percentage' => round($goal->getProgressPercentage(), 2),
            'is_completed' => $goal->isCompleted(),
            'estimated_completion' => $goal->getEstimatedCompletionDate()?->format('Y-m-d'),
            'days_remaining' => $goal->getDaysRemaining(),
            'monthly_contribution' => (float) ($goal->monthly_contribution ?? 0),
        ];
    }
    public function getFamilyGoalsOverview(Family $family): array
    {
        $goals = $family->savingsGoals()->where('is_active', true)->get();

        return [
            'total_goals' => $goals->count(),
            'completed_goals' => $goals->filter(fn($g) => $g->isCompleted())->count(),
            'in_progress_goals' => $goals->filter(fn($g) => !$g->isCompleted())->count(),
            'total_target' => (float) $goals->sum('target_amount'),
            'total_saved' => (float) $goals->sum('current_amount'),
            'overall_progress' => $goals->sum('target_amount') > 0
                ? round(($goals->sum('current_amount') / $goals->sum('target_amount')) * 100, 2)
                : 0,
            'goals' => $goals->map(fn($goal) => $this->getGoalProgress($goal))->toArray(),
        ];
    }

    public function processAutoContributions(): void
    {
        $today = now()->day;

        $goals = SavingsGoal::where('auto_contribute', true)
            ->where('is_active', true)
            ->where('contribution_day', $today)
            ->whereColumn('current_amount', '<', 'target_amount')
            ->get();

        foreach ($goals as $goal) {
            if ($goal->monthly_contribution > 0) {
                $this->contribute($goal, $goal->monthly_contribution);
            }
        }
    }
}
