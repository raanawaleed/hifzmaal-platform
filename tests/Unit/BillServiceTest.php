<?php

namespace Tests\Unit;

use App\Models\Bill;
use App\Models\Category;
use App\Models\Family;
use App\Models\User;
use App\Services\BillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BillService $billService;
    protected Family $family;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->billService = new BillService();

        $user = User::factory()->create();
        $this->family = Family::factory()->create(['owner_id' => $user->id]);
        $this->category = Category::factory()->create([
            'family_id' => $this->family->id,
            'type' => 'expense',
        ]);
    }

    protected function makeBill(array $attributes = []): Bill
    {
        return Bill::factory()->create(array_merge([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
        ], $attributes));
    }

    public function test_split_shares_sum_exactly_to_bill_amount(): void
    {
        // 100.00 across 3 people cannot split evenly in floats.
        $bill = $this->makeBill([
            'amount' => 100.00,
            'split_members' => [1, 2],
        ]);

        $shares = $this->billService->splitBillAmount($bill);

        $this->assertCount(3, $shares);
        $this->assertEquals(100.00, round(array_sum($shares), 2));

        // No share differs by more than one paisa (rounded to kill float noise).
        $this->assertLessThanOrEqual(0.01, round(max($shares) - min($shares), 2));
    }

    public function test_split_without_members_returns_full_amount(): void
    {
        $bill = $this->makeBill(['amount' => 4500, 'split_members' => null]);

        $this->assertEquals([4500.0], $this->billService->splitBillAmount($bill));
    }

    public function test_mark_as_paid_generates_next_monthly_bill(): void
    {
        $bill = $this->makeBill([
            'is_recurring' => true,
            'frequency' => 'monthly',
            'due_date' => '2026-07-10',
        ]);

        $result = $this->billService->markAsPaid($bill);

        $this->assertTrue($result);
        $this->assertEquals('paid', $bill->fresh()->status);

        $next = Bill::where('family_id', $this->family->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $this->assertEquals('2026-08-10', $next->due_date->toDateString());
    }

    public function test_mark_as_paid_twice_returns_false_and_no_duplicate(): void
    {
        $bill = $this->makeBill([
            'is_recurring' => true,
            'frequency' => 'monthly',
        ]);

        $this->assertTrue($this->billService->markAsPaid($bill));
        $this->assertFalse($this->billService->markAsPaid($bill->fresh()));

        // Original + exactly one next bill.
        $this->assertEquals(2, Bill::where('family_id', $this->family->id)->count());
    }

    public function test_non_recurring_bill_does_not_generate_next(): void
    {
        $bill = $this->makeBill(['is_recurring' => false]);

        $this->billService->markAsPaid($bill);

        $this->assertEquals(1, Bill::where('family_id', $this->family->id)->count());
    }

    public function test_check_overdue_bills_updates_status(): void
    {
        $this->makeBill([
            'status' => 'pending',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->billService->checkOverdueBills();

        $this->assertEquals(1, Bill::where('family_id', $this->family->id)
            ->where('status', 'overdue')->count());
    }
}
