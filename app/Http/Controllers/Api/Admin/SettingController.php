<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\PlatformSetting;
use App\Services\MetalPriceFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends ApiController
{
    /**
     * Languages the Zakat experience ships translations for. Keep in sync
     * with SUPPORTED_LOCALES in resources/js/i18n.js.
     */
    public const SUPPORTED_LANGUAGES = ['en', 'ar', 'ur', 'hi', 'bn', 'fr', 'de', 'es'];

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'metal_rates' => PlatformSetting::get('zakat.metal_rates'),
                'nisab' => PlatformSetting::get('zakat.nisab'),
                'default_language' => PlatformSetting::get('zakat.default_language', 'en'),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metal_rates.currency' => ['required_with:metal_rates', 'string', 'size:3'],
            'metal_rates.gold_per_gram' => ['required_with:metal_rates', 'numeric', 'min:0.01'],
            'metal_rates.silver_per_gram' => ['required_with:metal_rates', 'numeric', 'min:0.01'],
            'nisab.gold_grams' => ['required_with:nisab', 'numeric', 'min:0.01'],
            'nisab.silver_grams' => ['required_with:nisab', 'numeric', 'min:0.01'],
            'default_language' => ['sometimes', 'string', 'in:' . implode(',', self::SUPPORTED_LANGUAGES)],
        ]);

        if (isset($validated['metal_rates'])) {
            PlatformSetting::set('zakat.metal_rates', $validated['metal_rates'] + [
                'updated_by' => $request->user()->id,
            ]);
        }

        if (isset($validated['nisab'])) {
            PlatformSetting::set('zakat.nisab', $validated['nisab']);
        }

        if (isset($validated['default_language'])) {
            PlatformSetting::set('zakat.default_language', $validated['default_language']);
        }

        return response()->json([
            'message' => 'Platform settings updated.',
            'data' => [
                'metal_rates' => PlatformSetting::get('zakat.metal_rates'),
                'nisab' => PlatformSetting::get('zakat.nisab'),
                'default_language' => PlatformSetting::get('zakat.default_language', 'en'),
            ],
        ]);
    }

    /**
     * Public (unauthenticated) read of the platform-default UI language.
     * The SPA calls this for visitors/users who have never picked a
     * language themselves: their own choice (localStorage) wins, then
     * this platform default, then English.
     */
    public function defaultLanguage(): JsonResponse
    {
        return response()->json([
            'data' => [
                'default_language' => PlatformSetting::get('zakat.default_language', 'en'),
            ],
        ]);
    }

    /**
     * Pull live gold/silver prices and overwrite the metal_rates setting
     * with them. Superadmin can still edit the values by hand afterward —
     * this just gives them a fresh starting point instead of typing in
     * today's spot price every time.
     */
    public function refreshMetalRates(Request $request, MetalPriceFeedService $feed): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $rates = $feed->refreshRates(strtoupper($validated['currency'] ?? 'USD'));

        if ($rates === null) {
            // 422, not 5xx: this is an expected, actionable state (feed
            // not configured or the provider is down) — a 5xx would also
            // trip the frontend's generic "something went wrong on our
            // side" toast on top of this specific message.
            return response()->json([
                'message' => 'Could not fetch live metal prices. Check GOLDAPI_KEY is set and the provider is reachable, or enter rates manually below.',
                'error' => 'metal_price_feed_unavailable',
            ], 422);
        }

        return response()->json([
            'message' => 'Metal rates refreshed from live prices.',
            'data' => ['metal_rates' => $rates],
        ]);
    }
}
