<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class BudgetApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    protected User $user;
    protected Family $family;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->family = $this->createFamilyFor($this->user);
        $this->category = Category::factory()->create([
            'family_id' => $this->family->id,
            'type' => 'expense',
        ]);
    }

    public function test_owner_can_create_budget(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/budgets", [
                'category_id' => $this->category->id,
                'name' => 'Groceries Budget',
                'amount' => 30000,
                'period' => 'monthly',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budgets', [
            'family_id' => $this->family->id,
            'name' => 'Groceries Budget',
        ]);
    }

    public function test_viewer_cannot_create_budget(): void
    {
        $viewer = $this->addMember($this->family, 'viewer');

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/budgets", [
                'category_id' => $this->category->id,
                'name' => 'Not Allowed',
                'amount' => 1000,
                'period' => 'monthly',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    public function test_validation_rejects_end_date_before_start_date(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/budgets", [
                'category_id' => $this->category->id,
                'name' => 'Broken Budget',
                'amount' => 1000,
                'period' => 'monthly',
                'start_date' => now()->toDateString(),
                'end_date' => now()->subMonth()->toDateString(),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('end_date');
    }

    public function test_can_get_budget_overview(): void
    {
        Budget::factory()->count(2)->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/budgets/overview")
            ->assertStatus(200);
    }

    public function test_stranger_cannot_access_budgets(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/budgets")
            ->assertStatus(403);
    }
}
