<?php

namespace App\Console\Commands;

use App\Models\SavingsGoal;
use App\Services\SavingsGoalService;
use Illuminate\Console\Command;

class ProcessAutoSavingsContributions extends Command
{
    protected $signature = 'savings:process-auto-contributions';

    protected $description = 'Process automatic savings goal contributions';

    public function handle(SavingsGoalService $savingsGoalService): int
    {
        $this->info('Processing auto savings contributions...');

        $today = now()->day;

        $goals = SavingsGoal::where('auto_contribute', true)
            ->where('is_active', true)
            ->where('contribution_day', $today)
            ->whereColumn('current_amount', '<', 'target_amount')
            ->get();

        $processed = 0;

        foreach ($goals as $goal) {
            try {
                if ($goal->monthly_contribution > 0) {
                    $savingsGoalService->contribute($goal, $goal->monthly_contribution);
                    $processed++;
                    $this->info("Contributed {$goal->monthly_contribution} to goal: {$goal->name}");
                }
            } catch (\Exception $e) {
                $this->error("Failed to process contribution for goal {$goal->id}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$processed} auto contributions.");

        return self::SUCCESS;
    }
}