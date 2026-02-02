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
}