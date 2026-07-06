<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FamilyMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'relationship' => fake()->randomElement(['spouse', 'son', 'daughter', 'brother', 'sister', 'dependent']),
            'role' => 'member',
            'date_of_birth' => fake()->optional()->date('Y-m-d', '-18 years'),
            'is_active' => true,
            'spending_limit' => null,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => 'owner', 'relationship' => 'owner']);
    }

    public function viewer(): static
    {
        return $this->state(fn () => ['role' => 'viewer']);
    }
}
