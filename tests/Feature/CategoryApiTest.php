<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class CategoryApiTest extends TestCase
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

    public function test_owner_can_create_custom_category(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/categories", [
                'name' => 'Sadaqah',
                'type' => 'expense',
                'color' => '#00AA55',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', [
            'family_id' => $this->family->id,
            'name' => 'Sadaqah',
        ]);
    }

    public function test_viewer_cannot_create_category(): void
    {
        $viewer = $this->addMember($this->family, 'viewer');

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/categories", [
                'name' => 'Nope',
                'type' => 'expense',
            ])
            ->assertStatus(403);
    }

    public function test_invalid_color_is_rejected(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/categories", [
                'name' => 'Bad Color',
                'type' => 'expense',
                'color' => 'not-a-color',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('color');
    }

    public function test_stranger_cannot_list_categories(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/categories")
            ->assertStatus(403);
    }
}
