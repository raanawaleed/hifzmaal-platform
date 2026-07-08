<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class AccountDeletionApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    public function test_can_delete_own_account_with_correct_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);
        $originalEmail = $user->email; // actingAs() shares this instance with
        // the controller, which mutates it in place — capture first.

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/account', [
            'password' => 'secret1234',
        ]);

        $response->assertStatus(200);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('users', ['email' => $originalEmail]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_deletion_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/account', [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_deletion_is_blocked_while_user_owns_a_family(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);
        $this->createFamilyFor($user);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/account', [
            'password' => 'secret1234',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_deleted_account_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);
        $email = $user->email;

        $this->actingAs($user, 'sanctum')->deleteJson('/api/account', [
            'password' => 'secret1234',
        ])->assertStatus(200);

        $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'secret1234',
        ])->assertStatus(422);
    }
}
