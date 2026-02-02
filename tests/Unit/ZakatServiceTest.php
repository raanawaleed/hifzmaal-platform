<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\User;
use App\Services\ZakatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZakatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ZakatService $zakatService;
    protected User $user;
    protected Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->zakatService = new ZakatService();
        $this->user = User::factory()->create();
        $this->family = Family::factory()->create(['owner_id' => $this->user->id]);
    }

    public function test_can_calculate_zakat(): void
    {
        $data = [
            'cash_in_hand' => 50000,
            'cash_in_bank' => 150000,
            'gold_value' => 100000,
            'silver_value' => 0,
            'business_inventory' => 0,
            'investments' => 0,
            'loans_receivable' => 0,
            'other_assets' => 0,
            'debts' => 20000,
            'nisab_type' => 'silver',
        ];

        $calculation = $this->zakatService->calculateZakat($this->family, 1445, $data);

        $this->assertNotNull($calculation);
        $this->assertEquals(1445, $calculation->hijri_year);
        $this->assertEquals(300000, $calculation->total_assets);
        $this->assertEquals(280000, $calculation->zakatable_amount); // 300000 - 20000 debts
        $this->assertEquals(7000, $calculation->zakat_due); // 2.5% of 280000
    }

    public function test_zakat_is_zero_below_nisab(): void
    {
        $data = [
            'cash_in_hand' => 10000,
            'cash_in_bank' => 20000,
            'gold_value' => 0,
            'silver_value' => 0,
            'business_inventory' => 0,
            'investments' => 0,
            'loans_receivable' => 0,
            'other_assets' => 0,
            'debts' => 0,
            'nisab_type' => 'silver',
        ];

        $calculation = $this->zakatService->calculateZakat($this->family, 1445, $data);

        $this->assertEquals(0, $calculation->zakat_due);
        $this->assertFalse($calculation->isZakatDue());
    }

    public function test_can_record_zakat_payment(): void
    {
        $calculation = $this->zakatService->calculateZakat($this->family, 1445, [
            'cash_in_hand' => 100000,
            'cash_in_bank' => 100000,
            'debts' => 0,
            'nisab_type' => 'silver',
        ]);

        $paymentData = [
            'amount' => 2500,
            'payment_date' => now(),
            'type' => 'zakat',
            'recipient_name' => 'Test Recipient',
        ];

        $payment = $this->zakatService->recordPayment($calculation, $paymentData);

        $this->assertNotNull($payment);
        $this->assertEquals(2500, $payment->amount);
        $this->assertEquals(2500, $calculation->fresh()->zakat_paid);
        $this->assertEquals(2500, $calculation->fresh()->zakat_remaining); // 5000 - 2500
    }

    public function test_can_auto_calculate_from_accounts(): void
    {
        // Create accounts
        $this->family->accounts()->create([
            'name' => 'Cash',
            'type' => 'cash',
            'balance' => 50000,
            'initial_balance' => 50000,
            'include_in_zakat' => true,
            'is_active' => true,
        ]);

        $this->family->accounts()->create([
            'name' => 'Bank',
            'type' => 'bank',
            'balance' => 150000,
            'initial_balance' => 150000,
            'include_in_zakat' => true,
            'is_active' => true,
        ]);

        $calculation = $this->zakatService->autoCalculateFromAccounts($this->family, 1445);

        $this->assertEquals(50000, $calculation->cash_in_hand);
        $this->assertEquals(150000, $calculation->cash_in_bank);
        $this->assertEquals(200000, $calculation->total_assets);
    }
}