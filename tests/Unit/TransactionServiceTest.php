<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Category;
use App\Models\Family;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $transactionService;
    protected User $user;
    protected Family $family;
    protected Account $account;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transactionService = new TransactionService();
        
        $this->user = User::factory()->create();
        $this->family = Family::factory()->create(['owner_id' => $this->user->id]);
        $this->account = Account::factory()->create([
            'family_id' => $this->family->id,
            'balance' => 10000,
        ]);
        $this->category = Category::factory()->create([
            'type' => 'expense',
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_create_expense_transaction(): void
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 500,
            'date' => now()->format('Y-m-d'),
            'description' => 'Test expense',
        ];

        $transaction = $this->transactionService->createTransaction($this->family, $data);

        $this->assertNotNull($transaction);
        $this->assertEquals('expense', $transaction->type);
        $this->assertEquals(500, $transaction->amount);
        $this->assertEquals('approved', $transaction->status);
        
        // Check balance updated
        $this->assertEquals(9500, $this->account->fresh()->balance);
    }

    public function test_can_create_income_transaction(): void
    {
        $incomeCategory = Category::factory()->create(['type' => 'income']);
        
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $incomeCategory->id,
            'type' => 'income',
            'amount' => 5000,
            'date' => now()->format('Y-m-d'),
            'description' => 'Test income',
        ];

        $transaction = $this->transactionService->createTransaction($this->family, $data);

        $this->assertNotNull($transaction);
        $this->assertEquals('income', $transaction->type);
        $this->assertEquals(5000, $transaction->amount);
        
        // Check balance updated
        $this->assertEquals(15000, $this->account->fresh()->balance);
    }

    public function test_transaction_requires_approval_when_exceeds_limit(): void
    {
        // Create family member with spending limit
        $member = $this->family->members()->create([
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'relationship' => 'owner',
            'role' => 'editor',
            'spending_limit' => 1000,
        ]);

        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 1500,
            'date' => now()->format('Y-m-d'),
            'description' => 'Large expense',
        ];

        $transaction = $this->transactionService->createTransaction($this->family, $data);

        $this->assertEquals('pending', $transaction->status);
        $this->assertTrue($transaction->needs_approval);
        
        // Balance should not be updated yet
        $this->assertEquals(10000, $this->account->fresh()->balance);
    }

    public function test_can_approve_transaction(): void
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 500,
            'date' => now()->format('Y-m-d'),
            'description' => 'Test expense',
            'needs_approval' => true,
            'status' => 'pending',
        ];

        $transaction = $this->family->transactions()->create(array_merge($data, [
            'created_by' => $this->user->id,
        ]));

        $this->transactionService->approveTransaction($transaction);

        $this->assertEquals('approved', $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->approved_at);
        $this->assertEquals(9500, $this->account->fresh()->balance);
    }

    public function test_can_get_category_wise_expenses(): void
    {
        $category1 = Category::factory()->create(['type' => 'expense', 'name' => 'Food']);
        $category2 = Category::factory()->create(['type' => 'expense', 'name' => 'Transport']);

        // Create transactions
        $this->family->transactions()->create([
            'account_id' => $this->account->id,
            'category_id' => $category1->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'amount' => 1000,
            'date' => now(),
            'status' => 'approved',
        ]);

        $this->family->transactions()->create([
            'account_id' => $this->account->id,
            'category_id' => $category1->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'amount' => 500,
            'date' => now(),
            'status' => 'approved',
        ]);

        $this->family->transactions()->create([
            'account_id' => $this->account->id,
            'category_id' => $category2->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'amount' => 2000,
            'date' => now(),
            'status' => 'approved',
        ]);

        $expenses = $this->transactionService->getCategoryWiseExpenses(
            $this->family, 
            now()->month, 
            now()->year
        );

        $this->assertCount(2, $expenses);
        $this->assertEquals(2000, $expenses[0]['total']); // Transport (sorted by total desc)
        $this->assertEquals(1500, $expenses[1]['total']); // Food
    }
}