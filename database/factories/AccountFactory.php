<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        $initialBalance = fake()->randomFloat(2, 1000, 100000);

        return [
            'family_id' => Family::factory(),
            'name' => fake()->randomElement(['Main Account', 'Savings', 'Cash Wallet', 'Business Account']),
            'type' => fake()->randomElement(['cash', 'bank', 'wallet', 'savings']),
            'currency' => 'PKR',
            'balance' => $initialBalance,
            'initial_balance' => $initialBalance,
            'account_number' => fake()->optional()->numerify('####-####-####'),
            'bank_name' => fake()->optional()->randomElement(['HBL', 'UBL', 'MCB', 'Allied Bank']),
            'is_active' => true,
            'include_in_zakat' => true,
            'description' => fake()->optional()->sentence(),
        ];
    }
}