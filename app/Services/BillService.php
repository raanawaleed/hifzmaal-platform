<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Family;
use App\Events\BillDueReminder;
use App\Events\BillOverdue;
use Carbon\Carbon;

class BillService
{
    public function createBill(Family $family, array $data): Bill
    {
        return $family->bills()->create($data);
    }

    public function updateBill(Bill $bill, array $data): Bill
    {
        $bill->update($data);
        return $bill->fresh();
    }

    public function deleteBill(Bill $bill): void
    {
        $bill->delete();
    }

    public function markAsPaid(Bill $bill, ?int $transactionId = null): void
    {
        $bill->markAsPaid($transactionId);
    }

    public function getUpcomingBills(Family $family, int $days = 7): array
    {
        return $family->bills()
            ->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->with('category')
            ->orderBy('due_date')
            ->get()
            ->map(fn($bill) => [
                'id' => $bill->id,
                'name' => $bill->name,
                'type' => $bill->type,
                'amount' => (float) $bill->amount,
                'due_date' => $bill->due_date->format('Y-m-d'),
                'days_until_due' => $bill->getDaysUntilDue(),
                'provider' => $bill->provider,
                'category' => $bill->category->name,
            ])
            ->toArray();
    }

    public function getOverdueBills(Family $family): array
    {
        return $family->bills()
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->with('category')
            ->orderBy('due_date')
            ->get()
            ->map(fn($bill) => [
                'id' => $bill->id,
                'name' => $bill->name,
                'amount' => (float) $bill->amount,
                'due_date' => $bill->due_date->format('Y-m-d'),
                'days_overdue' => abs($bill->getDaysUntilDue()),
                'provider' => $bill->provider,
            ])
            ->toArray();
    }

    public function estimateNextBill(Bill $bill): float
    {
        if ($bill->average_amount) {
            return $bill->average_amount;
        }

        // Calculate average from last 3 payments if available
        $lastPayments = Bill::where('name', $bill->name)
            ->where('family_id', $bill->family_id)
            ->where('status', 'paid')
            ->orderBy('last_paid_date', 'desc')
            ->limit(3)
            ->pluck('amount');

        if ($lastPayments->isNotEmpty()) {
            return $lastPayments->average();
        }

        return $bill->amount;
    }

    public function sendBillReminders(): void
    {
        $bills = Bill::where('status', 'pending')
            ->get()
            ->filter(fn($bill) => $bill->shouldRemind());

        foreach ($bills as $bill) {
            event(new BillDueReminder($bill));
        }
    }

    public function checkOverdueBills(): void
    {
        $bills = Bill::where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();

        foreach ($bills as $bill) {
            $bill->update(['status' => 'overdue']);
            event(new BillOverdue($bill));
        }
    }

    public function splitBillAmount(Bill $bill): array
    {
        if (!$bill->split_members || empty($bill->split_members)) {
            return [(float) $bill->amount];
        }

        $memberCount = count($bill->split_members) + 1; // +1 for bill creator
        $perPersonAmount = $bill->amount / $memberCount;

        return array_fill(0, $memberCount, (float) $perPersonAmount);
    }

    public function getBillStatistics(Family $family): array
    {
        $bills = $family->bills;

        return [
            'total_bills' => $bills->count(),
            'pending_bills' => $bills->where('status', 'pending')->count(),
            'overdue_bills' => $bills->where('status', 'overdue')->count(),
            'paid_this_month' => $bills->where('status', 'paid')
                ->whereBetween('last_paid_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'total_due_amount' => (float) $bills->where('status', 'pending')->sum('amount'),
            'average_bill_amount' => (float) $bills->avg('amount'),
        ];
    }
}