<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'expense']);
        
        return [
            'family_id' => Family::factory(),
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'created_by' => User::factory(),
            'type' => $type,
            'amount' => fake()->randomFloat(2, 100, 50000),
            'currency' => 'PKR',
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'description' => fake()->sentence(),
            'notes' => fake()->optional()->paragraph(),
            'status' => 'approved',
            'needs_approval' => false,
            'is_recurring' => false,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'needs_approval' => true,
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_frequency' => fake()->randomElement(['monthly', 'weekly', 'yearly']),
        ]);
    }
}