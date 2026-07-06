<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_create_family(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/families', [
                'name' => 'Ahmed Family',
                'currency' => 'PKR',
                'locale' => 'en',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Family created successfully',
                'data' => [
                    'name' => 'Ahmed Family',
                    'currency' => 'PKR',
                ],
            ]);

        $this->assertDatabaseHas('families', [
            'name' => 'Ahmed Family',
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_can_list_families(): void
    {
        Family::factory()->count(3)->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/families');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_update_family(): void
    {
        $family = Family::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/families/{$family->id}", [
                'name' => 'Updated Family Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Family updated successfully',
            ]);

        $this->assertDatabaseHas('families', [
            'id' => $family->id,
            'name' => 'Updated Family Name',
        ]);
    }

    public function test_cannot_update_family_without_permission(): void
    {
        $otherUser = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/families/{$family->id}", [
                'name' => 'Updated Family Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_family(): void
    {
        $family = Family::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/families/{$family->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('families', [
            'id' => $family->id,
        ]);
    }

    public function test_can_view_own_family(): void
    {
        $family = Family::factory()->create(['owner_id' => $this->user->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/families/{$family->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $family->id);
    }

    public function test_cannot_view_another_users_family(): void
    {
        $otherUser = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $otherUser->id]);

        // IDOR regression: show() must reject non-members.
        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/families/{$family->id}")
            ->assertStatus(403);
    }

    public function test_cannot_delete_another_users_family(): void
    {
        $otherUser = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $otherUser->id]);

        // IDOR regression: destroy() must reject non-owners.
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/families/{$family->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('families', ['id' => $family->id, 'deleted_at' => null]);
    }

    public function test_member_can_view_family_but_not_delete_it(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->create([
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'relationship' => 'brother',
            'role' => 'member',
            'is_active' => true,
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/families/{$family->id}")
            ->assertStatus(200);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/families/{$family->id}")
            ->assertStatus(403);
    }
}