<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class AccountApiTest extends TestCase
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

    public function test_owner_can_create_account(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/accounts", [
                'name' => 'Main Cash',
                'type' => 'cash',
                'initial_balance' => 5000,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('accounts', [
            'family_id' => $this->family->id,
            'name' => 'Main Cash',
            'balance' => 5000,
        ]);
    }

    public function test_member_can_create_account(): void
    {
        $member = $this->addMember($this->family, 'member');

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/accounts", [
                'name' => 'Member Wallet',
                'type' => 'wallet',
                'initial_balance' => 100,
            ]);

        $response->assertStatus(201);
    }

    public function test_viewer_cannot_create_account(): void
    {
        $viewer = $this->addMember($this->family, 'viewer');

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/accounts", [
                'name' => 'Not Allowed',
                'type' => 'cash',
                'initial_balance' => 100,
            ]);

        $response->assertStatus(403);
    }

    public function test_stranger_cannot_list_accounts(): void
    {
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/accounts");

        $response->assertStatus(403);
    }

    public function test_validation_requires_bank_name_for_bank_accounts(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/accounts", [
                'name' => 'My Bank',
                'type' => 'bank',
                'initial_balance' => 1000,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('bank_name');
    }

    public function test_owner_can_update_and_delete_account(): void
    {
        $account = Account::factory()->create(['family_id' => $this->family->id]);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/families/{$this->family->id}/accounts/{$account->id}", [
                'name' => 'Renamed Account',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'name' => 'Renamed Account']);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/families/{$this->family->id}/accounts/{$account->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }
}
