<?php

namespace App\Services;

use App\Models\Family;
use App\Models\ZakatCalculation;
use App\Models\ZakatPayment;
use App\Events\ZakatCalculated;
use App\Events\ZakatDueReminder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ZakatService
{
    protected const ZAKAT_RATE = 0.025; // 2.5%
    protected const GOLD_NISAB_GRAMS = 87.48; // 7.5 tola
    protected const SILVER_NISAB_GRAMS = 612.36; // 52.5 tola

    public function calculateZakat(Family $family, int $hijriYear, array $data): ZakatCalculation
    {
        $nisabAmount = $this->getNisabAmount($data['nisab_type'] ?? 'silver', $family->currency);

        $calculation = $family->zakatCalculations()->updateOrCreate(
            ['hijri_year' => $hijriYear],
            [
                'calculation_date' => now(),
                'cash_in_hand' => $data['cash_in_hand'] ?? 0,
                'cash_in_bank' => $data['cash_in_bank'] ?? 0,
                'gold_value' => $data['gold_value'] ?? 0,
                'silver_value' => $data['silver_value'] ?? 0,
                'business_inventory' => $data['business_inventory'] ?? 0,
                'investments' => $data['investments'] ?? 0,
                'loans_receivable' => $data['loans_receivable'] ?? 0,
                'other_assets' => $data['other_assets'] ?? 0,
                'debts' => $data['debts'] ?? 0,
                'nisab_amount' => $nisabAmount,
                'nisab_type' => $data['nisab_type'] ?? 'silver',
                'asset_details' => $data['asset_details'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        $calculation->calculateZakat();

        event(new ZakatCalculated($calculation));

        return $calculation->fresh();
    }

    public function getNisabAmount(string $type = 'silver', string $currency = 'PKR'): float
    {
        try {
            $rates = $this->getCurrentMetalRates($currency);

            return match($type) {
                'gold' => self::GOLD_NISAB_GRAMS * $rates['gold_per_gram'],
                'silver' => self::SILVER_NISAB_GRAMS * $rates['silver_per_gram'],
                default => self::SILVER_NISAB_GRAMS * $rates['silver_per_gram'],
            };
        } catch (\Exception $e) {
            // Fallback to default values
            return $this->getDefaultNisabAmount($type, $currency);
        }
    }

    protected function getCurrentMetalRates(string $currency = 'PKR'): array
    {
        return Cache::remember("metal_rates_{$currency}", 3600, function () use ($currency) {
            // Mock data - integrate with real API like metalpriceapi.com or local gold market API
            return match($currency) {
                'PKR' => [
                    'gold_per_gram' => 9715,
                    'silver_per_gram' => 155,
                    'currency' => 'PKR',
                ],
                'USD' => [
                    'gold_per_gram' => 65,
                    'silver_per_gram' => 0.85,
                    'currency' => 'USD',
                ],
                default => [
                    'gold_per_gram' => 9715,
                    'silver_per_gram' => 155,
                    'currency' => 'PKR',
                ],
            };
        });
    }

    protected function getDefaultNisabAmount(string $type, string $currency): float
    {
        $defaults = [
            'PKR' => ['gold' => 850000, 'silver' => 95000],
            'USD' => ['gold' => 3000, 'silver' => 350],
            'EUR' => ['gold' => 2800, 'silver' => 320],
        ];

        return $defaults[$currency][$type] ?? $defaults['PKR'][$type];
    }

    public function recordPayment(ZakatCalculation $calculation, array $data): ZakatPayment
    {
        $payment = $calculation->payments()->create([
            'family_id' => $calculation->family_id,
            'recipient_id' => $data['recipient_id'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'] ?? now(),
            'type' => $data['type'] ?? 'zakat',
            'recipient_name' => $data['recipient_name'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Update calculation
        $calculation->increment('zakat_paid', $data['amount']);
        $calculation->update([
            'zakat_remaining' => max(0, $calculation->zakat_due - $calculation->zakat_paid)
        ]);

        return $payment;
    }

    public function autoCalculateFromAccounts(Family $family, int $hijriYear): ZakatCalculation
    {
        $cashInBank = $family->accounts()
            ->where('is_active', true)
            ->where('include_in_zakat', true)
            ->whereIn('type', ['bank', 'savings'])
            ->sum('balance');

        $cashInHand = $family->accounts()
            ->where('is_active', true)
            ->where('include_in_zakat', true)
            ->where('type', 'cash')
            ->sum('balance');

        return $this->calculateZakat($family, $hijriYear, [
            'cash_in_hand' => $cashInHand,
            'cash_in_bank' => $cashInBank,
            'nisab_type' => 'silver',
        ]);
    }

    public function sendZakatReminders(): void
    {
        $calculations = ZakatCalculation::where('zakat_remaining', '>', 0)
            ->with('family')
            ->get();

        foreach ($calculations as $calculation) {
            event(new ZakatDueReminder($calculation));
        }
    }

    public function getZakatHistory(Family $family): array
    {
        return $family->zakatCalculations()
            ->with('payments')
            ->orderBy('hijri_year', 'desc')
            ->get()
            ->map(fn($calc) => [
                'hijri_year' => $calc->hijri_year,
                'zakat_due' => $calc->zakat_due,
                'zakat_paid' => $calc->zakat_paid,
                'zakat_remaining' => $calc->zakat_remaining,
                'calculation_date' => $calc->calculation_date->format('Y-m-d'),
                'payments_count' => $calc->payments->count(),
                'is_fully_paid' => $calc->isFullyPaid(),
            ])
            ->toArray();
    }

    public function getCurrentHijriYear(): int
    {
        // Simple approximation - for production use proper Hijri calendar library
        $gregorianYear = now()->year;
        return $gregorianYear + 579; // Approximate conversion
    }
}