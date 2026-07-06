<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingsGoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'account_id' => null,
            'name' => fake()->randomElement(['Hajj Fund', 'Emergency Fund', 'Education Fund', 'Umrah Trip']),
            'type' => fake()->randomElement(['hajj', 'umrah', 'education', 'marriage', 'emergency', 'business', 'other']),
            'target_amount' => fake()->randomFloat(2, 50000, 1000000),
            'current_amount' => 0,
            'monthly_contribution' => fake()->optional()->randomFloat(2, 1000, 20000),
            'target_date' => fake()->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d'),
            'start_date' => now()->toDateString(),
            'description' => fake()->optional()->sentence(),
            'dua_reminder' => null,
            'auto_contribute' => false,
            'contribution_day' => null,
            'is_active' => true,
        ];
    }
}
