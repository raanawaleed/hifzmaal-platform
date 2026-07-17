<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pulls live gold/silver spot prices and writes them into the same
 * `zakat.metal_rates` platform setting the superadmin panel already reads
 * and edits by hand — this only automates *how that setting gets filled*,
 * it doesn't add a new code path for ZakatService to trust. If the API
 * call fails for any reason, the existing manually-set (or default) rates
 * are left untouched.
 *
 * Uses goldapi.io: GET https://www.goldapi.io/api/{symbol}/{currency}
 * with an `x-access-token` header, returning `price_gram_24k` directly —
 * no troy-ounce conversion needed. Requires GOLDAPI_KEY in .env; without
 * it, refreshRates() is a no-op and returns null.
 */
class MetalPriceFeedService
{
    public function refreshRates(string $currency = 'USD'): ?array
    {
        $apiKey = config('services.goldapi.key');

        if (blank($apiKey)) {
            return null;
        }

        $gold = $this->fetchPricePerGram('XAU', $currency, $apiKey);
        $silver = $this->fetchPricePerGram('XAG', $currency, $apiKey);

        if ($gold === null || $silver === null) {
            return null;
        }

        $rates = [
            'currency' => strtoupper($currency),
            'gold_per_gram' => $gold,
            'silver_per_gram' => $silver,
            'source' => 'goldapi.io',
            'fetched_at' => now()->toIso8601String(),
        ];

        PlatformSetting::set('zakat.metal_rates', $rates);

        return $rates;
    }

    protected function fetchPricePerGram(string $symbol, string $currency, string $apiKey): ?float
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['x-access-token' => $apiKey])
                ->get("https://www.goldapi.io/api/{$symbol}/{$currency}");

            if (! $response->successful()) {
                Log::warning('Metal price API request failed', [
                    'symbol' => $symbol,
                    'currency' => $currency,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $price = $response->json('price_gram_24k');

            return $price !== null ? round((float) $price, 2) : null;
        } catch (\Throwable $e) {
            Log::warning('Metal price API request threw an exception', [
                'symbol' => $symbol,
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
