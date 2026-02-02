<?php

use Carbon\Carbon;

if (!function_exists('format_currency')) {
    /**
     * Format currency amount
     */
    function format_currency(float $amount, string $currency = 'PKR'): string
    {
        $symbols = [
            'PKR' => 'Rs.',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'SAR' => 'SAR',
            'AED' => 'AED',
            'INR' => '₹',
            'BDT' => '৳',
        ];

        $symbol = $symbols[$currency] ?? $currency;

        return $symbol . ' ' . number_format($amount, 2);
    }
}

if (!function_exists('hijri_year')) {
    /**
     * Get current Hijri year (approximate)
     */
    function hijri_year(?Carbon $date = null): int
    {
        $date = $date ?? now();
        return $date->year + 579; // Approximate conversion
    }
}

if (!function_exists('is_halal_category')) {
    /**
     * Check if category is halal
     */
    function is_halal_category(string $categoryName): bool
    {
        $haram = ['interest', 'alcohol', 'gambling', 'lottery', 'haram'];

        foreach ($haram as $word) {
            if (stripos($categoryName, $word) !== false) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('get_barakah_tip')) {
    /**
     * Get random Barakah tip
     */
    function get_barakah_tip(): string
    {
        $tips = [
            "Give charity from what you love most. Allah says: 'You will never attain righteousness until you spend from that which you love.' (3:92)",
            "Make intention before spending. The Prophet ﷺ said: 'Actions are according to intentions.'",
            "Avoid extravagance. Allah says: 'Indeed, the wasteful are brothers of the devils.' (17:27)",
            "Seek halal income. The Prophet ﷺ said: 'Allah is pure and accepts only that which is pure.'",
            "Pay Zakat on time. It purifies your wealth and brings Barakah.",
            "Remember: Wealth is a trust (Amanah) from Allah.",
            "Be grateful for what you have. Gratitude increases blessings.",
            "Help your family members. Charity begins at home.",
        ];

        return $tips[array_rand($tips)];
    }
}

if (!function_exists('get_zakat_category_label')) {
    /**
     * Get Zakat category label
     */
    function get_zakat_category_label(string $category): string
    {
        $labels = config('hifzmaal.zakat_categories');
        return $labels[$category] ?? $category;
    }
}

if (!function_exists('calculate_days_in_month')) {
    /**
     * Calculate days between dates
     */
    function calculate_days_between(Carbon $startDate, Carbon $endDate): int
    {
        return $startDate->diffInDays($endDate);
    }
}

if (!function_exists('get_islamic_greeting')) {
    /**
     * Get Islamic greeting based on time
     */
    function get_islamic_greeting(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            return 'صبح بخیر (Good Morning)';
        } elseif ($hour < 17) {
            return 'السلام علیکم (Peace be upon you)';
        } else {
            return 'شام بخیر (Good Evening)';
        }
    }
}

if (!function_exists('percentage_change')) {
    /**
     * Calculate percentage change
     */
    function percentage_change(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return 0;
        }

        return (($newValue - $oldValue) / $oldValue) * 100;
    }
}

if (!function_exists('fiscal_month_start')) {
    /**
     * Get fiscal month start date
     */
    function fiscal_month_start(?Carbon $date = null): Carbon
    {
        $date = $date ?? now();
        return $date->copy()->startOfMonth();
    }
}

if (!function_exists('fiscal_month_end')) {
    /**
     * Get fiscal month end date
     */
    function fiscal_month_end(?Carbon $date = null): Carbon
    {
        $date = $date ?? now();
        return $date->copy()->endOfMonth();
    }
}
