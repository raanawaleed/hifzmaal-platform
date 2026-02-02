<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FamilyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true) . ' Family',
            'currency' => fake()->randomElement(['PKR', 'USD', 'EUR']),
            'locale' => fake()->randomElement(['en', 'ur']),
            'owner_id' => User::factory(),
            'settings' => null,
        ];
    }
}