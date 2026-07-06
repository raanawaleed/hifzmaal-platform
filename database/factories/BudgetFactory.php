<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'category_id' => Category::factory()->expense(),
            'name' => fake()->words(2, true) . ' Budget',
            'amount' => fake()->randomFloat(2, 5000, 100000),
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'alert_threshold' => 80.00,
            'is_active' => true,
        ];
    }
}
