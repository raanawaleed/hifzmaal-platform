<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class SavingsGoalApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    protected User $user;
    protected Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->family = $this->createFamilyFor($this->user);
    }

    public function test_owner_can_create_savings_goal(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/savings-goals", [
                'name' => 'Hajj Fund',
                'type' => 'hajj',
                'target_amount' => 500000,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('savings_goals', [
            'family_id' => $this->family->id,
            'name' => 'Hajj Fund',
        ]);
    }

    public function test_can_contribute_to_goal(): void
    {
        $goal = SavingsGoal::factory()->create([
            'family_id' => $this->family->id,
            'target_amount' => 10000,
            'current_amount' => 0,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/savings-goals/{$goal->id}/contribute", [
                'amount' => 2500,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(2500, (float) $goal->fresh()->current_amount);
    }

    public function test_contribution_is_clamped_at_target(): void
    {
        $goal = SavingsGoal::factory()->create([
            'family_id' => $this->family->id,
            'target_amount' => 10000,
            'current_amount' => 9500,
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/savings-goals/{$goal->id}/contribute", [
                'amount' => 5000,
            ])
            ->assertStatus(200);

        // Never exceeds the target.
        $this->assertEquals(10000, (float) $goal->fresh()->current_amount);
    }

    public function test_viewer_cannot_contribute(): void
    {
        $viewer = $this->addMember($this->family, 'viewer');

        $goal = SavingsGoal::factory()->create([
            'family_id' => $this->family->id,
        ]);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/savings-goals/{$goal->id}/contribute", [
                'amount' => 100,
            ])
            ->assertStatus(403);
    }

    public function test_stranger_cannot_access_goals(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/savings-goals")
            ->assertStatus(403);
    }
}
