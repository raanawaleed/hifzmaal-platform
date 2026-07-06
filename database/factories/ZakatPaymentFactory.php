<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\ZakatCalculation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZakatPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'zakat_calculation_id' => ZakatCalculation::factory(),
            'family_id' => Family::factory(),
            'recipient_id' => null,
            'transaction_id' => null,
            'amount' => fake()->randomFloat(2, 500, 50000),
            'payment_date' => now()->toDateString(),
            'type' => 'zakat',
            'recipient_name' => fake()->optional()->name(),
            'notes' => null,
        ];
    }
}
