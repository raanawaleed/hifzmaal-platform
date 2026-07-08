<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Notifications\FamilyInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class FamilyInvitationApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    protected User $owner;
    protected Family $family;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->family = $this->createFamilyFor($this->owner);
    }

    public function test_adding_a_member_with_an_email_sends_an_invitation(): void
    {
        Notification::fake();

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/members", [
                'name' => 'Fatima',
                'email' => 'fatima@example.com',
                'relationship' => 'spouse',
                'role' => 'member',
            ])->assertStatus(201);

        Notification::assertSentOnDemand(FamilyInvitationNotification::class);

        $member = FamilyMember::where('email', 'fatima@example.com')->firstOrFail();
        $this->assertNotNull($member->invitation_token);
        $this->assertNotNull($member->invitation_expires_at);
        $this->assertNull($member->user_id);
    }

    public function test_adding_a_member_without_an_email_sends_no_invitation(): void
    {
        Notification::fake();

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/members", [
                'name' => 'Young Son',
                'relationship' => 'son',
                'role' => 'viewer',
            ])->assertStatus(201);

        Notification::assertNothingSent();
    }

    public function test_invitation_preview_is_publicly_visible(): void
    {
        $member = FamilyMember::factory()->create([
            'family_id' => $this->family->id,
            'user_id' => null,
            'email' => 'fatima@example.com',
            'role' => 'member',
            'invitation_token' => 'test-token-123',
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/invitations/test-token-123');

        $response->assertStatus(200)
            ->assertJsonPath('data.family_name', $this->family->name)
            ->assertJsonPath('data.email', 'fatima@example.com');
    }

    public function test_invitation_preview_404s_for_unknown_token(): void
    {
        $this->getJson('/api/invitations/does-not-exist')->assertStatus(404);
    }

    public function test_invitation_preview_404s_for_expired_token(): void
    {
        FamilyMember::factory()->create([
            'family_id' => $this->family->id,
            'user_id' => null,
            'email' => 'fatima@example.com',
            'invitation_token' => 'expired-token',
            'invitation_expires_at' => now()->subDay(),
        ]);

        $this->getJson('/api/invitations/expired-token')->assertStatus(404);
    }

    public function test_matching_email_can_accept_invitation(): void
    {
        $member = FamilyMember::factory()->create([
            'family_id' => $this->family->id,
            'user_id' => null,
            'email' => 'fatima@example.com',
            'role' => 'member',
            'invitation_token' => 'test-token-123',
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $invitee = User::factory()->create(['email' => 'fatima@example.com']);

        $response = $this->actingAs($invitee, 'sanctum')
            ->postJson('/api/invitations/test-token-123/accept');

        $response->assertStatus(200);

        $member->refresh();
        $this->assertEquals($invitee->id, $member->user_id);
        $this->assertNotNull($member->invitation_accepted_at);
        $this->assertNull($member->invitation_token);

        $this->assertTrue($invitee->hasAccessToFamily($this->family));
    }

    public function test_mismatched_email_cannot_accept_invitation(): void
    {
        FamilyMember::factory()->create([
            'family_id' => $this->family->id,
            'user_id' => null,
            'email' => 'fatima@example.com',
            'invitation_token' => 'test-token-123',
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $stranger = User::factory()->create(['email' => 'someone-else@example.com']);

        $response = $this->actingAs($stranger, 'sanctum')
            ->postJson('/api/invitations/test-token-123/accept');

        $response->assertStatus(403)->assertJson(['error' => 'email_mismatch']);
    }

    public function test_cannot_accept_an_already_accepted_invitation_twice(): void
    {
        $invitee = User::factory()->create(['email' => 'fatima@example.com']);

        FamilyMember::factory()->create([
            'family_id' => $this->family->id,
            'user_id' => $invitee->id,
            'email' => 'fatima@example.com',
            'invitation_token' => null,
            'invitation_accepted_at' => now(),
        ]);

        // The (now-cleared) token can no longer be replayed.
        $this->actingAs($invitee, 'sanctum')
            ->postJson('/api/invitations/test-token-123/accept')
            ->assertStatus(404);
    }

    public function test_owner_can_resend_invitation(): void
    {
        Notification::fake();

        $member = FamilyMember::factory()->create([
            'family_id' => $this->family->id,
            'user_id' => null,
            'email' => 'fatima@example.com',
            'invitation_token' => 'old-token',
            'invitation_expires_at' => now()->subDay(), // expired
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/members/{$member->id}/resend-invitation");

        $response->assertStatus(200);

        $member->refresh();
        $this->assertNotEquals('old-token', $member->invitation_token);
        $this->assertTrue($member->invitation_expires_at->isFuture());
        Notification::assertSentOnDemand(FamilyInvitationNotification::class);
    }
}
