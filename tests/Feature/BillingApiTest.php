<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class BillingApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    /** Give a user an active Stripe subscription without calling Stripe. */
    protected function subscribeToPro(User $user): void
    {
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_'.$user->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'quantity' => 1,
        ]);
    }

    public function test_billing_status_defaults_to_free_plan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/billing/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.plan', 'free')
            ->assertJsonPath('data.subscribed', false)
            ->assertJsonPath('data.on_trial', false);
    }

    public function test_billing_status_reflects_generic_trial(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['trial_ends_at' => now()->addDays(14)])->save();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/billing/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.plan', 'pro')
            ->assertJsonPath('data.on_trial', true);
    }

    public function test_billing_status_reflects_active_subscription(): void
    {
        $user = User::factory()->create();
        $this->subscribeToPro($user);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/billing/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.plan', 'pro')
            ->assertJsonPath('data.subscribed', true);
    }

    // (canceled(), not cancelled() — Cashier uses US spelling.)

    public function test_free_plan_is_blocked_from_creating_a_second_family(): void
    {
        $user = User::factory()->create();
        $this->createFamilyFor($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/families', [
            'name' => 'Second Family',
            'currency' => 'USD',
            'locale' => 'en',
        ]);

        $response->assertStatus(403)->assertJson(['error' => 'plan_limit_reached']);
    }

    public function test_pro_plan_can_create_multiple_families(): void
    {
        $user = User::factory()->create();
        $this->subscribeToPro($user);
        $this->createFamilyFor($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/families', [
            'name' => 'Second Family',
            'currency' => 'USD',
            'locale' => 'en',
        ]);

        $response->assertStatus(201);
    }

    public function test_free_plan_family_is_blocked_from_adding_members_past_the_limit(): void
    {
        config(['billing.free.max_members_per_family' => 2]);

        $owner = User::factory()->create();
        $family = $this->createFamilyFor($owner); // owner counts as member #1

        $this->actingAs($owner, 'sanctum')->postJson("/api/families/{$family->id}/members", [
            'name' => 'Second Member',
            'relationship' => 'spouse',
            'role' => 'member',
        ])->assertStatus(201);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/families/{$family->id}/members", [
            'name' => 'Third Member',
            'relationship' => 'son',
            'role' => 'member',
        ]);

        $response->assertStatus(403)->assertJson(['error' => 'plan_limit_reached']);
    }

    public function test_pro_family_has_no_member_limit(): void
    {
        config(['billing.free.max_members_per_family' => 1]);

        $owner = User::factory()->create();
        $this->subscribeToPro($owner);
        $family = $this->createFamilyFor($owner);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/families/{$family->id}/members", [
            'name' => 'Second Member',
            'relationship' => 'spouse',
            'role' => 'member',
        ]);

        $response->assertStatus(201);
    }

    public function test_checkout_rejects_unconfigured_plan(): void
    {
        config(['billing.plans.pro_monthly.price_id' => null]);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/billing/checkout', [
            'plan' => 'pro_monthly',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('plan');
    }

    public function test_checkout_requires_verified_email(): void
    {
        config(['billing.plans.pro_monthly.price_id' => 'price_test']);
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/billing/checkout', [
            'plan' => 'pro_monthly',
        ]);

        // Laravel's EnsureEmailIsVerified middleware aborts JSON requests
        // with 403 (some versions/docs describe 409 — not this one).
        $response->assertStatus(403);
    }

    public function test_checkout_rejects_when_already_subscribed(): void
    {
        config(['billing.plans.pro_monthly.price_id' => 'price_test']);
        $user = User::factory()->create();
        $this->subscribeToPro($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/billing/checkout', [
            'plan' => 'pro_monthly',
        ]);

        $response->assertStatus(422)->assertJson(['error' => 'already_subscribed']);
    }

    public function test_portal_requires_an_existing_stripe_customer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/billing/portal');

        $response->assertStatus(422)->assertJson(['error' => 'no_stripe_customer']);
    }
}
