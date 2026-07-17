<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    public function test_creating_a_transaction_is_logged_with_the_causer_and_family(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyFor($user);
        $account = Account::factory()->create(['family_id' => $family->id, 'balance' => 10000]);
        $category = Category::factory()->create(['family_id' => $family->id, 'type' => 'expense']);

        $this->actingAs($user, 'sanctum')->postJson("/api/families/{$family->id}/transactions", [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 250,
            'date' => now()->format('Y-m-d'),
            'description' => 'Groceries',
        ])->assertStatus(201);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/families/{$family->id}/activity");

        $response->assertStatus(200);
        $entry = collect($response->json('data'))->firstWhere('subject_type', 'Transaction');
        $this->assertNotNull($entry, 'Expected a logged Transaction activity entry');
        $this->assertSame('created', $entry['event']);
        $this->assertSame($user->id, $entry['causer']['id']);
    }

    public function test_stranger_cannot_view_another_familys_activity(): void
    {
        $owner = User::factory()->create();
        $family = $this->createFamilyFor($owner);
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/families/{$family->id}/activity")
            ->assertStatus(403);
    }

    public function test_updating_only_an_untracked_field_does_not_add_a_second_log_entry(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyFor($user);
        // The factory create() below is itself a logged "created" event
        // (name/balance are tracked) — that's the 1 entry we expect to
        // still be the only one after the update.
        $account = Account::factory()->create(['family_id' => $family->id, 'name' => 'Main', 'balance' => 100]);

        $account->update(['description' => 'irrelevant note change']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/families/{$family->id}/activity");

        // description isn't in Account::getActivitylogOptions()'s logOnly list,
        // and logOnlyDirty()+dontLogEmptyChanges() means a change to only an
        // untracked field shouldn't add a second ("updated") log entry.
        $entries = collect($response->json('data'))->where('subject_type', 'Account');
        $this->assertCount(1, $entries);
        $this->assertSame('created', $entries->first()['event']);
    }
}
