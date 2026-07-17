<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    /**
     * Seed default Zakat metal rates and nisab configuration.
     * Superadmins manage these from the admin panel; values here are
     * only sensible starting points (PKR per gram / per tola).
     */
    public function run(): void
    {
        PlatformSetting::firstOrCreate(
            ['key' => 'zakat.metal_rates'],
            ['value' => [
                'currency' => 'PKR',
                'gold_per_gram' => 18500,
                'silver_per_gram' => 235,
                'updated_by' => null,
            ]]
        );

        PlatformSetting::firstOrCreate(
            ['key' => 'zakat.nisab'],
            ['value' => [
                // Classical thresholds: 87.48g gold / 612.36g silver
                'gold_grams' => 87.48,
                'silver_grams' => 612.36,
            ]]
        );

        // Default UI language for visitors/members who never picked one
        // themselves (en, ar, ur, hi, bn, fr, de, es).
        PlatformSetting::firstOrCreate(
            ['key' => 'zakat.default_language'],
            ['value' => 'en']
        );
    }
}
