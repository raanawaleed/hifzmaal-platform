<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use App\Models\ZakatCalculation;
use App\Models\ZakatPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    protected User $user;
    protected Family $family;
    protected Account $account;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->family = $this->createFamilyFor($this->user, ['currency' => 'USD']);
        $this->account = Account::factory()->create(['family_id' => $this->family->id, 'balance' => 10000]);
        $this->category = Category::factory()->create(['family_id' => $this->family->id, 'type' => 'expense']);
    }

    public function test_can_export_transactions_as_csv(): void
    {
        Transaction::factory()->count(3)->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/families/{$this->family->id}/transactions/export");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Date,Type,Status,Amount', $content);
        $this->assertSame(4, substr_count($content, "\n")); // header + 3 rows
    }

    public function test_stranger_cannot_export_another_familys_transactions(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->get("/api/families/{$this->family->id}/transactions/export")
            ->assertStatus(403);
    }

    public function test_can_download_monthly_report_pdf(): void
    {
        Transaction::factory()->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'status' => 'approved',
            'amount' => 250,
            'date' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/families/{$this->family->id}/reports/monthly?month=".now()->month.'&year='.now()->year);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_monthly_report_requires_valid_month_and_year(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->get("/api/families/{$this->family->id}/reports/monthly?month=13&year=2026")
            ->assertStatus(422);
    }

    public function test_can_download_zakat_certificate_pdf(): void
    {
        $calculation = ZakatCalculation::factory()->create([
            'family_id' => $this->family->id,
            'hijri_year' => 1447,
        ]);

        ZakatPayment::factory()->create([
            'zakat_calculation_id' => $calculation->id,
            'family_id' => $this->family->id,
            'amount' => 100,
            'type' => 'zakat',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/families/{$this->family->id}/zakat/{$calculation->id}/certificate");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_certificate_404s_for_calculation_in_another_family(): void
    {
        $otherFamily = $this->createFamilyFor(User::factory()->create());
        $calculation = ZakatCalculation::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($this->user, 'sanctum')
            ->get("/api/families/{$this->family->id}/zakat/{$calculation->id}/certificate")
            ->assertStatus(404);
    }
}
