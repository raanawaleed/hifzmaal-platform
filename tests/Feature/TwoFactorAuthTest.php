<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function currentCodeFor(string $secret): string
    {
        return (new Google2FA())->getCurrentOtp($secret);
    }

    public function test_user_can_enable_and_confirm_two_factor_authentication(): void
    {
        $user = User::factory()->create();

        $enableResponse = $this->actingAs($user, 'sanctum')->postJson('/api/two-factor/enable');
        $enableResponse->assertStatus(200)->assertJsonStructure(['data' => ['secret', 'otpauth_url']]);

        $secret = $enableResponse->json('data.secret');
        $this->assertFalse($user->fresh()->hasEnabledTwoFactorAuthentication());

        $confirmResponse = $this->actingAs($user, 'sanctum')->postJson('/api/two-factor/confirm', [
            'code' => $this->currentCodeFor($secret),
        ]);

        $confirmResponse->assertStatus(200)->assertJsonStructure(['data' => ['recovery_codes']]);
        $this->assertCount(8, $confirmResponse->json('data.recovery_codes'));
        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_confirm_rejects_an_invalid_code(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/two-factor/enable');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/two-factor/confirm', ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->assertFalse($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_login_requires_two_factor_challenge_once_enabled(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);
        $secret = (new Google2FA())->generateSecretKey();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['recovery-code-1'],
        ])->save();

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson(['two_factor_required' => true])
            ->assertJsonStructure(['two_factor_token']);
        $this->assertArrayNotHasKey('token', $loginResponse->json());

        $challengeResponse = $this->postJson('/api/login/two-factor-challenge', [
            'two_factor_token' => $loginResponse->json('two_factor_token'),
            'code' => $this->currentCodeFor($secret),
        ]);

        $challengeResponse->assertStatus(200)
            ->assertJsonStructure(['user'])
            ->assertCookie(config('session.cookie'));
    }

    public function test_two_factor_challenge_rejects_wrong_code(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);
        $secret = (new Google2FA())->generateSecretKey();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $this->postJson('/api/login/two-factor-challenge', [
            'two_factor_token' => $loginResponse->json('two_factor_token'),
            'code' => '000000',
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_recovery_code_can_be_used_once_to_complete_challenge(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);
        $secret = (new Google2FA())->generateSecretKey();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['abc123-def456', 'ghi789-jkl012'],
        ])->save();

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);
        $token = $loginResponse->json('two_factor_token');

        $this->postJson('/api/login/two-factor-challenge', [
            'two_factor_token' => $token,
            'recovery_code' => 'abc123-def456',
        ])->assertStatus(200)->assertCookie(config('session.cookie'));

        $this->assertCount(1, $user->fresh()->two_factor_recovery_codes);
        $this->assertSame(['ghi789-jkl012'], $user->fresh()->two_factor_recovery_codes);

        // Re-using the challenge token (already consumed) should fail even
        // with a fresh login — request a new one.
        $secondLogin = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);
        $this->postJson('/api/login/two-factor-challenge', [
            'two_factor_token' => $secondLogin->json('two_factor_token'),
            'recovery_code' => 'abc123-def456', // already used
        ])->assertStatus(422)->assertJsonValidationErrors('recovery_code');
    }

    public function test_user_can_disable_two_factor_with_correct_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);
        $user->forceFill([
            'two_factor_secret' => (new Google2FA())->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['a', 'b'],
        ])->save();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/two-factor', ['password' => 'secret1234'])
            ->assertStatus(200);

        $this->assertFalse($user->fresh()->hasEnabledTwoFactorAuthentication());
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_disable_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234')]);
        $user->forceFill([
            'two_factor_secret' => (new Google2FA())->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/two-factor', ['password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
    }
}
