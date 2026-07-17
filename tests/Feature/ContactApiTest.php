<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\User;
use App\Notifications\ContactAcknowledgmentNotification;
use App\Notifications\ContactReplyNotification;
use App\Notifications\NewContactInquiryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regular;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin', 'web');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('superadmin');

        $this->regular = User::factory()->create();
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'subject' => 'Question about zakat calculation',
            'message' => 'How does the nisab threshold work?',
        ], $overrides);
    }

    // ── Public submission ────────────────────────────────────────────

    public function test_public_submission_creates_row_and_sends_notifications(): void
    {
        Notification::fake();

        $this->postJson('/api/contact', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'subject' => 'Question about zakat calculation',
            'status' => 'new',
            'user_id' => null,
        ]);

        Notification::assertSentOnDemand(
            ContactAcknowledgmentNotification::class,
            function ($notification, $channels, $notifiable) {
                $this->assertEquals(['mail'], $channels);
                $this->assertEquals('ayesha@example.com', $notifiable->routes['mail']);

                $mail = $notification->toMail($notifiable);
                $this->assertStringContainsString('Question about zakat calculation', $mail->subject);

                return true;
            }
        );

        Notification::assertSentTo(
            $this->admin,
            NewContactInquiryNotification::class,
            function ($notification, $channels) {
                $this->assertContains('mail', $channels);
                $this->assertContains('database', $channels);
                $this->assertEquals('ayesha@example.com', $notification->toArray($this->admin)['email']);

                return true;
            }
        );

        // Regular users are not platform staff — they must not be alerted.
        Notification::assertNotSentTo($this->regular, NewContactInquiryNotification::class);
    }

    public function test_logged_in_submission_links_user_id(): void
    {
        Notification::fake();

        $this->actingAs($this->regular, 'sanctum')
            ->postJson('/api/contact', $this->validPayload())
            ->assertStatus(201);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'ayesha@example.com',
            'user_id' => $this->regular->id,
        ]);
    }

    public function test_submission_is_validated(): void
    {
        $this->postJson('/api/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => '',
            'message' => str_repeat('x', 5001),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_submission_is_throttled(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/contact', $this->validPayload())->assertStatus(201);
        }

        $this->postJson('/api/contact', $this->validPayload())->assertStatus(429);
    }

    // ── Admin access control ─────────────────────────────────────────

    public function test_regular_users_cannot_access_admin_contact_endpoints(): void
    {
        $message = ContactMessage::factory()->create();

        $endpoints = [
            ['get', '/api/admin/contact-messages'],
            ['get', "/api/admin/contact-messages/{$message->id}"],
            ['put', "/api/admin/contact-messages/{$message->id}"],
            ['post', "/api/admin/contact-messages/{$message->id}/reply"],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $this->actingAs($this->regular, 'sanctum')
                ->json($method, $uri)
                ->assertStatus(403);
        }
    }

    // ── Admin list / filters ─────────────────────────────────────────

    public function test_admin_can_list_contact_messages_with_replies_count(): void
    {
        $messages = ContactMessage::factory()->count(3)->create();
        ContactMessageReply::create([
            'contact_message_id' => $messages[0]->id,
            'user_id' => $this->admin->id,
            'body' => 'We are looking into this.',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/contact-messages');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'total', 'per_page']);

        $this->assertEquals(3, $response->json('total'));

        $withReply = collect($response->json('data'))->firstWhere('id', $messages[0]->id);
        $this->assertEquals(1, $withReply['replies_count']);
    }

    public function test_admin_can_filter_by_status(): void
    {
        ContactMessage::factory()->count(2)->create(['status' => 'new']);
        ContactMessage::factory()->create(['status' => 'resolved']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/contact-messages?status=resolved');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
        $this->assertEquals('resolved', $response->json('data.0.status'));
    }

    public function test_admin_can_search_messages(): void
    {
        ContactMessage::factory()->create(['name' => 'Findable Person']);
        ContactMessage::factory()->create(['email' => 'findme@example.com']);
        ContactMessage::factory()->create(['subject' => 'Findable subject line']);
        ContactMessage::factory()->create([
            'name' => 'Someone Else',
            'email' => 'other@example.com',
            'subject' => 'Unrelated',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/contact-messages?search=find');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
    }

    // ── Admin show ───────────────────────────────────────────────────

    public function test_admin_can_view_message_with_replies(): void
    {
        $message = ContactMessage::factory()->create();
        $reply = ContactMessageReply::create([
            'contact_message_id' => $message->id,
            'user_id' => $this->admin->id,
            'body' => 'Thanks for reaching out.',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/contact-messages/{$message->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $message->id)
            ->assertJsonPath('data.email', $message->email)
            ->assertJsonPath('data.replies.0.id', $reply->id)
            ->assertJsonPath('data.replies.0.body', 'Thanks for reaching out.')
            ->assertJsonPath('data.replies.0.admin_name', $this->admin->name);
    }

    // ── Admin status update ──────────────────────────────────────────

    public function test_admin_can_update_status(): void
    {
        $message = ContactMessage::factory()->create(['status' => 'new']);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/contact-messages/{$message->id}", ['status' => 'resolved'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'resolved');

        $this->assertEquals('resolved', $message->fresh()->status);
    }

    public function test_status_update_rejects_invalid_status(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/contact-messages/{$message->id}", ['status' => 'spam'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // ── Admin reply ──────────────────────────────────────────────────

    public function test_admin_reply_stores_row_emails_submitter_and_advances_status(): void
    {
        Notification::fake();

        $message = ContactMessage::factory()->create([
            'status' => 'new',
            'subject' => 'Billing question',
            'email' => 'submitter@example.com',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/contact-messages/{$message->id}/reply", [
                'body' => 'Your subscription renews on the 1st.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'Your subscription renews on the 1st.')
            ->assertJsonPath('data.admin_name', $this->admin->name);

        $this->assertDatabaseHas('contact_message_replies', [
            'contact_message_id' => $message->id,
            'user_id' => $this->admin->id,
            'body' => 'Your subscription renews on the 1st.',
        ]);

        Notification::assertSentOnDemand(
            ContactReplyNotification::class,
            function ($notification, $channels, $notifiable) {
                $this->assertEquals(['mail'], $channels);
                $this->assertEquals('submitter@example.com', $notifiable->routes['mail']);

                $mail = $notification->toMail($notifiable);
                $this->assertEquals('Re: Billing question', $mail->subject);
                $this->assertContains('Your subscription renews on the 1st.', $mail->introLines);

                return true;
            }
        );

        $this->assertEquals('in_progress', $message->fresh()->status);
    }

    public function test_reply_does_not_change_resolved_status(): void
    {
        Notification::fake();

        $message = ContactMessage::factory()->create(['status' => 'resolved']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/contact-messages/{$message->id}/reply", [
                'body' => 'Following up once more.',
            ])
            ->assertStatus(201);

        $this->assertEquals('resolved', $message->fresh()->status);
    }

    public function test_reply_is_validated(): void
    {
        Notification::fake();

        $message = ContactMessage::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/contact-messages/{$message->id}/reply", ['body' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/contact-messages/{$message->id}/reply", [
                'body' => str_repeat('x', 5001),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body']);

        Notification::assertNothingSent();
        $this->assertEquals(0, ContactMessageReply::count());
    }
}
