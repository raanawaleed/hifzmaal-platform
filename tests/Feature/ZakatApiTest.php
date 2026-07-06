<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\ZakatCalculation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class ZakatApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    protected User $user;
    protected Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->family = $this->createFamilyFor($this->user, ['currency' => 'PKR']);

        PlatformSetting::set('zakat.metal_rates', [
            'currency' => 'PKR',
            'gold_per_gram' => 20000,
            'silver_per_gram' => 250,
        ]);
        PlatformSetting::set('zakat.nisab', [
            'gold_grams' => 87.48,
            'silver_grams' => 612.36,
        ]);
    }

    public function test_owner_can_create_zakat_calculation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/zakat", [
                'hijri_year' => 1447,
                'cash_in_hand' => 100000,
                'cash_in_bank' => 400000,
                'debts' => 50000,
                'nisab_type' => 'silver',
            ]);

        $response->assertStatus(201);

        $calculation = ZakatCalculation::where('family_id', $this->family->id)->firstOrFail();

        // 500,000 assets − 50,000 debts = 450,000 zakatable.
        // Nisab (silver): 612.36 g × 250 = 153,090 → above nisab.
        // Zakat due: 450,000 × 2.5% = 11,250.
        $this->assertEquals(450000, (float) $calculation->zakatable_amount);
        $this->assertEquals(11250, (float) $calculation->zakat_due);
    }

    public function test_no_zakat_due_below_nisab(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/zakat", [
                'hijri_year' => 1447,
                'cash_in_hand' => 10000,
                'cash_in_bank' => 0,
                'nisab_type' => 'silver',
            ])
            ->assertStatus(201);

        $calculation = ZakatCalculation::where('family_id', $this->family->id)->firstOrFail();
        $this->assertEquals(0, (float) $calculation->zakat_due);
    }

    public function test_can_record_payment_and_remaining_updates(): void
    {
        $calculation = $this->makeCalculationWithDue();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/zakat/{$calculation->id}/payments", [
                'amount' => 5000,
                'type' => 'zakat',
                'recipient_name' => 'Local Charity',
            ]);

        $response->assertStatus(201);

        $calculation->refresh();
        $this->assertEquals(5000, (float) $calculation->zakat_paid);
        $this->assertEquals(6250, (float) $calculation->zakat_remaining);
    }

    public function test_overpayment_is_rejected(): void
    {
        $calculation = $this->makeCalculationWithDue();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/zakat/{$calculation->id}/payments", [
                'amount' => 999999,
                'type' => 'zakat',
                'recipient_name' => 'Local Charity',
            ]);

        $response->assertStatus(422);
        $this->assertEquals(0, (float) $calculation->fresh()->zakat_paid);
    }

    public function test_nisab_amount_endpoint_uses_platform_settings(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/zakat/nisab-amount?type=silver");

        $response->assertStatus(200);

        // 612.36 g × 250 PKR = 153,090
        $this->assertEquals(153090, (float) data_get($response->json(), 'data.nisab_amount', data_get($response->json(), 'nisab_amount')));
    }

    public function test_stranger_cannot_access_zakat(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/zakat")
            ->assertStatus(403);
    }

    protected function makeCalculationWithDue(): ZakatCalculation
    {
        // 500,000 − 50,000 = 450,000 → due 11,250
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/zakat", [
                'hijri_year' => 1447,
                'cash_in_hand' => 100000,
                'cash_in_bank' => 400000,
                'debts' => 50000,
                'nisab_type' => 'silver',
            ]);

        return ZakatCalculation::where('family_id', $this->family->id)->firstOrFail();
    }
}
