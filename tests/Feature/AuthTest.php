<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'locale' => 'en',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'token', 'user' => ['id', 'name', 'email', 'roles']]);

        $this->assertDatabaseHas('users', ['email' => 'ahmed@example.com']);
    }

    public function test_register_rejects_weak_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'password' => 'aaaaaaaa', // no numbers
            'password_confirmation' => 'aaaaaaaa',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Ahmed Ali',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'roles']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret1234'),
            'is_active' => false,
            'suspended_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $response->assertStatus(403)->assertJson(['error' => 'account_suspended']);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_suspended_user_with_existing_token_is_blocked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $user->forceFill(['is_active' => false, 'suspended_at' => now()])->save();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $response->assertStatus(403)->assertJson(['error' => 'account_suspended']);

        // The token must have been revoked.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_endpoint_returns_roles(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'roles', 'is_active']]);
    }

    public function test_forgot_password_returns_generic_message(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        // Existing email
        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertStatus(200);

        // Unknown email gets the exact same response — no enumeration.
        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])
            ->assertStatus(200);
    }

    public function test_reset_password_email_renders_a_working_link(): void
    {
        // Notification::fake() alone would hide a broken toMail() — it never
        // gets called. This app has no `password.reset` named route (it's
        // API-only), so the notification must be told to use the SPA's own
        // page — regression test for that exact bug.
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertStatus(200);

        Notification::assertSentTo($user, QueuedResetPassword::class, function (QueuedResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, config('app.frontend_url').'/reset-password?token=')
                && str_contains($url, 'email='.urlencode($user->email));
        });
    }

    public function test_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('auth-token');
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('newpassword1', $user->fresh()->password));

        // All previous tokens are revoked after a reset.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ]);

        $response->assertStatus(422);
    }

    public function test_registration_starts_a_pro_trial(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        $user = User::where('email', 'ahmed@example.com')->firstOrFail();

        $this->assertNotNull($user->trial_ends_at);
        $this->assertTrue($user->onGenericTrial());
        $this->assertTrue($user->hasProAccess());
    }

    public function test_registration_sends_a_verification_email(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        $user = User::where('email', 'ahmed@example.com')->firstOrFail();

        Notification::assertSentTo($user, QueuedVerifyEmail::class);
    }

    public function test_can_resend_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/email/verification-notification')
            ->assertStatus(200);

        Notification::assertSentTo($user, QueuedVerifyEmail::class);
    }

    public function test_resend_verification_short_circuits_when_already_verified(): void
    {
        Notification::fake();
        $user = User::factory()->create(); // already verified by default

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/email/verification-notification')
            ->assertStatus(200)
            ->assertJson(['message' => 'Your email is already verified.']);

        Notification::assertNothingSent();
    }

    public function test_signed_verification_link_verifies_email(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->get($url);

        $response->assertRedirect();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_link_rejects_wrong_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('someone-else@example.com'),
        ]);

        $this->get($url)->assertRedirect();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
