<?php

namespace App\Services;

use App\Models\Family;
use App\Models\PlatformSetting;
use App\Models\ZakatCalculation;
use App\Models\ZakatPayment;
use App\Events\ZakatCalculated;
use App\Events\ZakatDueReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            $nisab = PlatformSetting::get('zakat.nisab', []);

            $goldGrams = $nisab['gold_grams'] ?? self::GOLD_NISAB_GRAMS;
            $silverGrams = $nisab['silver_grams'] ?? self::SILVER_NISAB_GRAMS;

            return match($type) {
                'gold' => round($goldGrams * $rates['gold_per_gram'], 2),
                default => round($silverGrams * $rates['silver_per_gram'], 2),
            };
        } catch (\Exception $e) {
            // Fallback to default values
            return $this->getDefaultNisabAmount($type, $currency);
        }
    }

    protected function getCurrentMetalRates(string $currency = 'PKR'): array
    {
        // Superadmin-managed rates from the admin panel. Rates are stored in
        // the platform's base currency; other currencies fall back to defaults.
        $rates = PlatformSetting::get('zakat.metal_rates');

        if ($rates && ($rates['currency'] ?? null) === $currency) {
            return $rates;
        }

        throw new \RuntimeException("No metal rates configured for {$currency}");
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
        return DB::transaction(function () use ($calculation, $data) {
            // Lock the calculation so concurrent payments can't overpay.
            $calculation = ZakatCalculation::whereKey($calculation->id)->lockForUpdate()->first();

            if ($data['amount'] > $calculation->zakat_remaining) {
                throw ValidationException::withMessages([
                    'amount' => ["Payment exceeds remaining Zakat due ({$calculation->zakat_remaining})."],
                ]);
            }

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

            $calculation->zakat_paid = $calculation->zakat_paid + $data['amount'];
            $calculation->zakat_remaining = max(0, $calculation->zakat_due - $calculation->zakat_paid);
            $calculation->save();

            return $payment;
        });
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
        return $gregorianYear - 579; // e.g. 2026 CE ≈ 1447 AH
    }
}