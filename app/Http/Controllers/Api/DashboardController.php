<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Services\TransactionService;
use App\Services\BudgetService;
use App\Services\BillService;
use App\Services\SavingsGoalService;
use App\Services\ZakatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected BudgetService $budgetService,
        protected BillService $billService,
        protected SavingsGoalService $savingsGoalService,
        protected ZakatService $zakatService,
    ) {}

    public function index(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Financial Overview
        $totalBalance = $family->getTotalBalance();
        $monthlyIncome = $family->getMonthlyIncome($currentMonth, $currentYear);
        $monthlyExpense = $family->getMonthlyExpense($currentMonth, $currentYear);
        $netIncome = $monthlyIncome - $monthlyExpense;

        // Recent Transactions
        $recentTransactions = $this->transactionService->getRecentTransactions($family, 5);

        // Budget Overview
        $budgetOverview = $this->budgetService->getFamilyBudgetOverview($family);

        // Bills
        $upcomingBills = $this->billService->getUpcomingBills($family, 7);
        $overdueBills = $this->billService->getOverdueBills($family);

        // Savings Goals
        $savingsOverview = $this->savingsGoalService->getFamilyGoalsOverview($family);

        // Category-wise Expenses
        $categoryExpenses = $this->transactionService->getCategoryWiseExpenses($family, $currentMonth, $currentYear);

        // Monthly Trend (last 6 months)
        $monthlyTrend = $this->transactionService->getMonthlyTrend($family, 6);

        // Zakat Status
        $currentHijriYear = $this->zakatService->getCurrentHijriYear();
        $zakatCalculation = $family->zakatCalculations()
            ->where('hijri_year', $currentHijriYear)
            ->first();

        // Pending Approvals
        $pendingApprovals = $family->transactions()
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'data' => [
                'financial_overview' => [
                    'total_balance' => (float) $totalBalance,
                    'monthly_income' => (float) $monthlyIncome,
                    'monthly_expense' => (float) $monthlyExpense,
                    'net_income' => (float) $netIncome,
                    'currency' => $family->currency,
                ],
                'recent_transactions' => $recentTransactions,
                'budget_overview' => $budgetOverview,
                'bills' => [
                    'upcoming' => $upcomingBills,
                    'overdue' => $overdueBills,
                    'upcoming_count' => count($upcomingBills),
                    'overdue_count' => count($overdueBills),
                ],
                'savings_overview' => $savingsOverview,
                'category_expenses' => $categoryExpenses,
                'monthly_trend' => $monthlyTrend,
                'zakat_status' => $zakatCalculation ? [
                    'hijri_year' => $zakatCalculation->hijri_year,
                    'zakat_due' => (float) $zakatCalculation->zakat_due,
                    'zakat_paid' => (float) $zakatCalculation->zakat_paid,
                    'zakat_remaining' => (float) $zakatCalculation->zakat_remaining,
                    'is_zakat_due' => $zakatCalculation->isZakatDue(),
                ] : null,
                'pending_approvals' => $pendingApprovals,
                'family_members' => $family->getActiveMembers(),
            ],
        ]);
    }

    public function insights(Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $currentMonth = now()->month;
        $currentYear = now()->year;
        $previousMonth = now()->subMonth()->month;
        $previousYear = now()->subMonth()->year;

        // Compare current month vs previous month
        $currentMonthExpense = $family->getMonthlyExpense($currentMonth, $currentYear);
        $previousMonthExpense = $family->getMonthlyExpense($previousMonth, $previousYear);
        $expenseChange = $previousMonthExpense > 0 
            ? (($currentMonthExpense - $previousMonthExpense) / $previousMonthExpense) * 100 
            : 0;

        // Top spending categories
        $topCategories = $this->transactionService->getCategoryWiseExpenses($family, $currentMonth, $currentYear);
        $topCategories = array_slice($topCategories, 0, 5);

        // Budget alerts
        $budgets = $family->budgets()
            ->where('is_active', true)
            ->current()
            ->with('category')
            ->get();

        $budgetAlerts = $budgets->filter(fn($budget) => $budget->shouldAlert())
            ->map(fn($budget) => [
                'budget_name' => $budget->name,
                'category' => $budget->category->name,
                'percentage_used' => round($budget->getPercentageUsed(), 2),
                'amount' => (float) $budget->amount,
                'spent' => (float) $budget->getSpentAmount(),
            ])
            ->values();

        // Financial health score (0-100)
        $healthScore = $this->calculateFinancialHealthScore($family);

        // Tips and recommendations
        $tips = $this->generateFinancialTips($family, $healthScore);

        return response()->json([
            'data' => [
                'expense_comparison' => [
                    'current_month' => (float) $currentMonthExpense,
                    'previous_month' => (float) $previousMonthExpense,
                    'change_percentage' => round($expenseChange, 2),
                    'trend' => $expenseChange > 0 ? 'increasing' : ($expenseChange < 0 ? 'decreasing' : 'stable'),
                ],
                'top_spending_categories' => $topCategories,
                'budget_alerts' => $budgetAlerts,
                'financial_health_score' => $healthScore,
                'tips' => $tips,
            ],
        ]);
    }

    protected function calculateFinancialHealthScore(Family $family): int
    {
        $score = 100;
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Deduct points for negative factors
        $income = $family->getMonthlyIncome($currentMonth, $currentYear);
        $expense = $family->getMonthlyExpense($currentMonth, $currentYear);

        // Expense ratio (should be < 80% of income)
        if ($income > 0) {
            $expenseRatio = ($expense / $income) * 100;
            if ($expenseRatio > 100) {
                $score -= 30; // Spending more than earning
            } elseif ($expenseRatio > 80) {
                $score -= 15; // High expense ratio
            }
        }

        // Budget adherence
        $budgets = $family->budgets()->current()->get();
        $overBudgetCount = $budgets->filter(fn($b) => $b->isOverBudget())->count();
        if ($overBudgetCount > 0) {
            $score -= ($overBudgetCount * 10);
        }

        // Overdue bills
        $overdueBills = $family->bills()->where('status', 'overdue')->count();
        $score -= ($overdueBills * 5);

        // Savings goals progress
        $activeGoals = $family->savingsGoals()->where('is_active', true)->count();
        if ($activeGoals > 0) {
            $score += 10; // Bonus for having savings goals
        }

        // Zakat payment
        $currentHijriYear = $this->zakatService->getCurrentHijriYear();
        $zakatCalc = $family->zakatCalculations()->where('hijri_year', $currentHijriYear)->first();
        if ($zakatCalc && $zakatCalc->isFullyPaid()) {
            $score += 10; // Bonus for fulfilling zakat obligation
        }

        return max(0, min(100, $score));
    }

    protected function generateFinancialTips(Family $family, int $healthScore): array
    {
        $tips = [];

        // Base tips on health score
        if ($healthScore < 50) {
            $tips[] = [
                'type' => 'critical',
                'title' => 'Urgent: Review Your Expenses',
                'message' => 'Your spending is significantly high. Consider cutting unnecessary expenses.',
                'icon' => '⚠️',
            ];
        }

        // Budget tips
        $budgets = $family->budgets()->current()->get();
        $overBudgetCount = $budgets->filter(fn($b) => $b->isOverBudget())->count();
        if ($overBudgetCount > 0) {
            $tips[] = [
                'type' => 'warning',
                'title' => 'Budget Alert',
                'message' => "You have {$overBudgetCount} budget(s) that are exceeded.",
                'icon' => '📊',
            ];
        }

        // Savings tips
        $totalBalance = $family->getTotalBalance();
        $monthlyExpense = $family->getMonthlyExpense(now()->month, now()->year);
        $emergencyFundMonths = $monthlyExpense > 0 ? $totalBalance / $monthlyExpense : 0;
        
        if ($emergencyFundMonths < 3) {
            $tips[] = [
                'type' => 'advice',
                'title' => 'Build Emergency Fund',
                'message' => 'Aim to save at least 3-6 months of expenses for emergencies.',
                'icon' => '💰',
            ];
        }

        // Islamic finance tips
        $tips[] = [
            'type' => 'barakah',
            'title' => 'Barakah Reminder',
            'message' => 'Remember: "Allah will deprive usury of all blessing, but will give increase for deeds of charity" (2:276)',
            'icon' => '🤲',
        ];

        // Zakat reminder
        $currentHijriYear = $this->zakatService->getCurrentHijriYear();
        $zakatCalc = $family->zakatCalculations()->where('hijri_year', $currentHijriYear)->first();
        if ($zakatCalc && $zakatCalc->zakat_remaining > 0) {
            $tips[] = [
                'type' => 'zakat',
                'title' => 'Zakat Pending',
                'message' => "You have {$family->currency} {$zakatCalc->zakat_remaining} pending zakat payment.",
                'icon' => '☪️',
            ];
        }

        return $tips;
    }
}