<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'parent_id' => null,
            'name' => fake()->unique()->words(2, true),
            'name_ur' => null,
            'type' => fake()->randomElement(['income', 'expense']),
            'icon' => fake()->optional()->word(),
            'color' => fake()->hexColor(),
            'is_halal' => true,
            'is_system' => false,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => 'income']);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => 'expense']);
    }

    public function system(): static
    {
        return $this->state(fn () => ['family_id' => null, 'is_system' => true]);
    }
}
