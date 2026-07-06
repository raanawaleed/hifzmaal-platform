<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZakatRecipientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'name' => fake()->name(),
            'contact' => fake()->optional()->phoneNumber(),
            'category' => fake()->randomElement([
                'fuqara', 'masakin', 'amilin', 'muallaf',
                'riqab', 'gharimin', 'fisabilillah', 'ibnus_sabil',
            ]),
            'address' => fake()->optional()->address(),
            'notes' => null,
            'is_active' => true,
        ];
    }
}
