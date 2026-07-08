<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    /**
     * Register a new user
     * 
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Ahmed Ali"),
     *             @OA\Property(property="email", type="string", format="email", example="ahmed@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="locale", type="string", example="en")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', PasswordRule::min(8)->letters()->numbers(), 'confirmed'],
            'locale' => ['nullable', 'string', 'in:en,ur,hi,bn'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'locale' => $validated['locale'] ?? 'en',
        ]);

        // Not mass-assignable on purpose (billing field); no-card-required
        // trial — Cashier carries it into the first real subscription too.
        $user->forceFill([
            'trial_ends_at' => now()->addDays((int) config('billing.trial_days')),
        ])->save();

        event(new Registered($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Login user
     * 
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="ahmed@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Invalid credentials")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account has been suspended. Please contact support.',
                'error' => 'account_suspended',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Logout user
     * 
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(@OA\Property(property="message", type="string"))
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout successful']);
    }

    /**
     * Get authenticated user
     * 
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get current user",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="User details")
     * )
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Send a password reset link
     *
     * @OA\Post(
     *     path="/api/forgot-password",
     *     summary="Request a password reset email",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Reset link sent if the email exists")
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Always the same response — never reveal whether the email exists.
        return response()->json([
            'message' => 'If that email address exists, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset the password using an emailed token
     *
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Reset password with token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","email","password","password_confirmation"},
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset"),
     *     @OA\Response(response=422, description="Invalid or expired token")
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', PasswordRule::min(8)->letters()->numbers(), 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Invalidate every existing session/token after a reset.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Your password has been reset. Please log in again.']);
    }

    /**
     * Resend the verification email to the authenticated user.
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Your email is already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent. Please check your inbox.']);
    }

    /**
     * Handle the signed link from the verification email. Not behind
     * auth:sanctum on purpose — the browser opening this link has no API
     * token, only the signature + hash-of-email prove ownership.
     */
    public function verifyEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $frontend = config('app.frontend_url');
        $user = User::find($id);

        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect($frontend.'/login?verified=0');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect($frontend.'/login?verified=1');
    }

    /**
     * Permanently delete the authenticated user's account. Blocked while
     * they still own a family — ownership must be transferred or the
     * family deleted first, otherwise its data would be orphaned.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        if ($user->ownedFamilies()->exists()) {
            throw ValidationException::withMessages([
                'password' => ['Delete or transfer ownership of every family you own before deleting your account.'],
            ]);
        }

        if ($user->subscribed('default')) {
            $user->subscription('default')->cancelNow();
        }

        $user->tokens()->delete();
        $user->familyMemberships()->update(['is_active' => false]);

        // Anonymize rather than hard-delete: transactions this user created
        // or approved in families they don't own still need a valid
        // created_by/approved_by (restrict-on-delete FK) for the audit
        // trail. Soft-deleting afterwards blocks login and hides the row.
        $user->forceFill([
            'name' => 'Deleted User',
            'email' => 'deleted-'.$user->id.'-'.Str::random(8).'@deleted.hifzmaal.invalid',
            'password' => Hash::make(Str::random(40)),
            'is_active' => false,
        ])->save();

        $user->delete();

        return response()->json(['message' => 'Your account has been deleted.']);
    }
}