<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'category_id' => Category::factory()->expense(),
            'name' => fake()->randomElement(['Electricity Bill', 'Gas Bill', 'Internet Bill', 'School Fees']),
            'type' => fake()->randomElement(['electricity', 'gas', 'water', 'internet', 'mobile', 'rent', 'school_fees', 'other']),
            'amount' => fake()->randomFloat(2, 500, 50000),
            'average_amount' => null,
            'due_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'frequency' => 'monthly',
            'is_recurring' => true,
            'auto_pay' => false,
            'account_id' => null,
            'provider' => fake()->optional()->company(),
            'account_number' => fake()->optional()->numerify('########'),
            'split_members' => null,
            'reminder_days' => 3,
            'status' => 'pending',
            'last_paid_date' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid', 'last_paid_date' => now()->toDateString()]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => 'overdue',
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }
}
