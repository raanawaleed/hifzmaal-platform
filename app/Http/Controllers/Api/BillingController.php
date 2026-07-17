<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class BillingController extends ApiController
{
    /**
     * Unauthenticated — powers the public marketing pricing page. No plan
     * IDs or Stripe details, just what's safe to show a logged-out visitor.
     */
    public function pricing(): JsonResponse
    {
        return response()->json([
            'data' => [
                'currency' => strtoupper(config('billing.currency')),
                'trial_days' => config('billing.trial_days'),
                'free' => config('billing.free'),
                'pro_monthly_price' => config('billing.display_prices.pro_monthly'),
                'pro_yearly_price' => config('billing.display_prices.pro_yearly'),
            ],
        ]);
    }

    /**
     * Current plan, trial, and family/member usage vs. limits — drives the
     * billing screen and any "upgrade to Pro" prompts in the SPA.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        return response()->json([
            'data' => [
                'plan' => $user->hasProAccess() ? 'pro' : 'free',
                'subscribed' => $user->subscribed('default'),
                'on_trial' => $user->onGenericTrial(),
                'trial_ends_at' => $user->trial_ends_at,
                'on_grace_period' => $subscription?->onGracePeriod() ?? false,
                'cancelled' => $subscription?->canceled() ?? false,
                'ends_at' => $subscription?->ends_at,
                'pm_type' => $user->pm_type,
                'pm_last_four' => $user->pm_last_four,
                'families' => [
                    'used' => $user->ownedFamilies()->count(),
                    'limit' => $user->familyLimit(),
                ],
                'plans' => collect(config('billing.plans'))
                    ->filter(fn ($plan) => filled($plan['price_id']))
                    ->map(fn ($plan, $key) => ['key' => $key, 'label' => $plan['label']])
                    ->values(),
            ],
        ]);
    }

    /**
     * Start a Stripe Checkout session for a Pro subscription. The trial
     * (if any) carries over automatically via Cashier's generic-trial logic.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:pro_monthly,pro_yearly'],
        ]);

        $priceId = config("billing.plans.{$validated['plan']}.price_id");

        if (blank($priceId)) {
            throw ValidationException::withMessages([
                'plan' => ['This plan is not available right now. Please contact support.'],
            ]);
        }

        $user = $request->user();

        if ($user->subscribed('default')) {
            return response()->json([
                'message' => 'You already have an active subscription. Manage it from the billing portal.',
                'error' => 'already_subscribed',
            ], 422);
        }

        $frontend = config('app.frontend_url');

        try {
            $checkout = $user->newSubscription('default', $priceId)->checkout([
                'success_url' => $frontend.'/billing?checkout=success',
                'cancel_url' => $frontend.'/billing?checkout=cancelled',
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe checkout session creation failed', [
                'user_id' => $user->id,
                'plan' => $validated['plan'],
                'stripe_error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Billing is temporarily unavailable. Please try again shortly.',
                'error' => 'stripe_unavailable',
            ], 503);
        }

        return response()->json([
            'url' => $checkout->asStripeCheckoutSession()->url,
        ]);
    }

    /**
     * Redirect target for Stripe's customer billing portal (update card,
     * view invoices, cancel). Requires an existing Stripe customer.
     */
    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();

        if (blank($user->stripe_id)) {
            return response()->json([
                'message' => 'No billing account yet — subscribe to Pro first.',
                'error' => 'no_stripe_customer',
            ], 422);
        }

        try {
            $url = $user->billingPortalUrl(config('app.frontend_url').'/billing');
        } catch (ApiErrorException $e) {
            Log::error('Stripe billing portal session creation failed', [
                'user_id' => $user->id,
                'stripe_error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Billing is temporarily unavailable. Please try again shortly.',
                'error' => 'stripe_unavailable',
            ], 503);
        }

        return response()->json(['url' => $url]);
    }
}
