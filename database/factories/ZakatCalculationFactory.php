<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZakatCalculationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'hijri_year' => now()->year - 579,
            'calculation_date' => now()->toDateString(),
            'cash_in_hand' => fake()->randomFloat(2, 0, 100000),
            'cash_in_bank' => fake()->randomFloat(2, 0, 500000),
            'gold_value' => 0,
            'silver_value' => 0,
            'business_inventory' => 0,
            'investments' => 0,
            'loans_receivable' => 0,
            'other_assets' => 0,
            'debts' => 0,
            'total_assets' => 0,
            'nisab_amount' => 175350,
            'nisab_type' => 'silver',
            'zakatable_amount' => 0,
            'zakat_due' => 0,
            'zakat_paid' => 0,
            'zakat_remaining' => 0,
            'asset_details' => null,
            'notes' => null,
        ];
    }
}
