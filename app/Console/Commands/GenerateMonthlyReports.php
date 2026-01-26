<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Services\TransactionService;
use App\Services\BudgetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GenerateMonthlyReports extends Command
{
    protected $signature = 'reports:generate-monthly {--family-id=}';

    protected $description = 'Generate monthly financial reports for families';

    public function handle(
        TransactionService $transactionService,
        BudgetService $budgetService
    ): int {
        $this->info('Generating monthly reports...');

        $familyId = $this->option('family-id');

        $families = $familyId
            ? Family::where('id', $familyId)->get()
            : Family::all();

        $generated = 0;

        foreach ($families as $family) {
            try {
                $month = now()->subMonth()->month;
                $year = now()->subMonth()->year;

                $report = [
                    'family' => $family->name,
                    'period' => now()->subMonth()->format('F Y'),
                    'income' => $family->getMonthlyIncome($month, $year),
                    'expense' => $family->getMonthlyExpense($month, $year),
                    'net' => $family->getMonthlyIncome($month, $year) - $family->getMonthlyExpense($month, $year),
                    'category_expenses' => $transactionService->getCategoryWiseExpenses($family, $month, $year),
                    'budget_overview' => $budgetService->getFamilyBudgetOverview($family),
                ];

                // Here you can send email or save to database
                $this->info("Generated report for family: {$family->name}");
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Income', number_format($report['income'], 2)],
                        ['Expense', number_format($report['expense'], 2)],
                        ['Net', number_format($report['net'], 2)],
                    ]
                );

                $generated++;
            } catch (\Exception $e) {
                $this->error("Failed to generate report for family {$family->id}: {$e->getMessage()}");
            }
        }

        $this->info("Generated {$generated} monthly reports.");

        return self::SUCCESS;
    }
}