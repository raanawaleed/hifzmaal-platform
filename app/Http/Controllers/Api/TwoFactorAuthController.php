<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthController extends ApiController
{
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'enabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
            ],
        ]);
    }

    /**
     * Step 1 of enabling 2FA: generate a secret and hand back the otpauth
     * URL for a QR code. Not active yet — hasEnabledTwoFactorAuthentication()
     * requires two_factor_confirmed_at too, set only by confirm() below.
     */
    public function enable(Request $request, Google2FA $google2fa): JsonResponse
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            throw ValidationException::withMessages([
                'two_factor' => ['Two-factor authentication is already enabled.'],
            ]);
        }

        $secret = $google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return response()->json([
            'data' => [
                'secret' => $secret,
                'otpauth_url' => $this->otpAuthUrl($user->email, $secret),
            ],
        ]);
    }

    /**
     * Step 2: user submits a code from their authenticator app to prove
     * the secret from enable() actually works before we call it active.
     */
    public function confirm(Request $request, Google2FA $google2fa): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            throw ValidationException::withMessages([
                'code' => ['Start two-factor setup first.'],
            ]);
        }

        // verifyKey() can return 0 (a valid match with zero time-step
        // drift) — a plain truthy check would wrongly reject that.
        if ($google2fa->verifyKey($user->two_factor_secret, $request->code) === false) {
            throw ValidationException::withMessages([
                'code' => ['That code is invalid or has expired.'],
            ]);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        return response()->json([
            'message' => 'Two-factor authentication is now enabled.',
            // Shown once — same as recovery-codes regeneration below.
            'data' => ['recovery_codes' => $recoveryCodes],
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => 'Two-factor authentication has been disabled.']);
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            throw ValidationException::withMessages([
                'two_factor' => ['Two-factor authentication is not enabled.'],
            ]);
        }

        $recoveryCodes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $recoveryCodes])->save();

        return response()->json(['data' => ['recovery_codes' => $recoveryCodes]]);
    }

    /**
     * @return array<int, string>
     */
    protected function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::random(10).'-'.Str::random(10))
            ->all();
    }

    protected function otpAuthUrl(string $email, string $secret): string
    {
        $issuer = rawurlencode(config('app.name'));
        $label = rawurlencode($issuer.':'.$email);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }
}
