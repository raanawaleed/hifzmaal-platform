<?php

namespace App\Http\Controllers\Api;

use App\Models\Family;
use App\Models\ZakatCalculation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends ApiController
{
    /**
     * Income vs. expense by category for a given month, as a downloadable
     * PDF — the "can I trust this app with my records" artifact.
     */
    public function monthly(Request $request, Family $family): Response
    {
        $this->authorize('view', $family);

        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);

        $month = (int) $validated['month'];
        $year = (int) $validated['year'];

        $transactions = $family->transactions()
            ->whereIn('type', ['income', 'expense'])
            ->where('status', 'approved')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('category')
            ->get();

        $byType = fn (string $type) => $transactions->where('type', $type)
            ->groupBy('category_id')
            ->map(fn ($rows) => [
                'category' => $rows->first()->category->name,
                'total' => (float) $rows->sum('amount'),
                'count' => $rows->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $income = $byType('income');
        $expense = $byType('expense');
        $totalIncome = (float) $income->sum('total');
        $totalExpense = (float) $expense->sum('total');

        $pdf = Pdf::loadView('reports.monthly', [
            'family' => $family,
            'monthLabel' => Carbon::create($year, $month, 1)->format('F Y'),
            'income' => $income,
            'expense' => $expense,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'net' => $totalIncome - $totalExpense,
            'generatedAt' => now(),
        ]);

        return $pdf->download("monthly-report-{$family->id}-{$year}-{$month}.pdf");
    }

    /**
     * A record-keeping certificate for a Zakat calculation and every
     * payment made against it — not a religious ruling, just a receipt.
     */
    public function zakatCertificate(Family $family, ZakatCalculation $calculation): Response
    {
        $this->authorize('view', $family);

        if ($calculation->family_id !== $family->id) {
            abort(404);
        }

        $calculation->load(['payments' => fn ($q) => $q->orderBy('payment_date')]);

        $pdf = Pdf::loadView('reports.zakat-certificate', [
            'family' => $family,
            'calculation' => $calculation,
            'payments' => $calculation->payments,
            'generatedAt' => now(),
        ]);

        return $pdf->download("zakat-certificate-{$calculation->hijri_year}AH-{$family->id}.pdf");
    }
}
