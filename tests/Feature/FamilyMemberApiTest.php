<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class FamilyMemberApiTest extends TestCase
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

    public function test_owner_can_add_member(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/members", [
                'name' => 'Fatima',
                'email' => 'fatima@example.com',
                'relationship' => 'spouse',
                'role' => 'member',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('family_members', [
            'family_id' => $this->family->id,
            'email' => 'fatima@example.com',
            'role' => 'member',
        ]);
    }

    public function test_member_cannot_manage_members(): void
    {
        $member = $this->addMember($this->family, 'member');

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/members", [
                'name' => 'Someone',
                'relationship' => 'brother',
                'role' => 'member',
            ]);

        $response->assertStatus(403);
    }

    public function test_old_role_names_are_rejected(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/members", [
                'name' => 'Someone',
                'relationship' => 'brother',
                'role' => 'editor', // legacy role removed
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('role');
    }

    public function test_duplicate_member_email_in_family_is_rejected(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/members", [
                'name' => 'Fatima',
                'email' => 'fatima@example.com',
                'relationship' => 'spouse',
                'role' => 'member',
            ])
            ->assertStatus(201);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/members", [
                'name' => 'Fatima Two',
                'email' => 'fatima@example.com',
                'relationship' => 'sister',
                'role' => 'viewer',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_second_owner_is_rejected(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/members", [
                'name' => 'Impostor',
                'relationship' => 'owner',
                'role' => 'owner',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('role');
    }
}
