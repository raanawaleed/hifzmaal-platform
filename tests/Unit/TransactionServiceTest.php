<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransactionException;
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
            'role' => 'member',
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

    public function test_expense_exceeding_balance_throws(): void
    {
        $this->expectException(InsufficientBalanceException::class);

        $this->transactionService->createTransaction($this->family, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 99999,
            'date' => now()->format('Y-m-d'),
        ]);
    }

    public function test_transfer_moves_balance_between_accounts(): void
    {
        $target = Account::factory()->create([
            'family_id' => $this->family->id,
            'balance' => 500,
        ]);

        $this->transactionService->createTransaction($this->family, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'transfer',
            'amount' => 3000,
            'date' => now()->format('Y-m-d'),
            'transfer_to_account_id' => $target->id,
        ]);

        $this->assertEquals(7000, (float) $this->account->fresh()->balance);
        $this->assertEquals(3500, (float) $target->fresh()->balance);
    }

    public function test_transfer_exceeding_balance_throws(): void
    {
        $target = Account::factory()->create([
            'family_id' => $this->family->id,
            'balance' => 0,
        ]);

        $this->expectException(InsufficientBalanceException::class);

        $this->transactionService->createTransaction($this->family, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'transfer',
            'amount' => 99999,
            'date' => now()->format('Y-m-d'),
            'transfer_to_account_id' => $target->id,
        ]);
    }

    public function test_transfer_to_same_account_throws(): void
    {
        $this->expectException(InvalidTransactionException::class);

        $this->transactionService->createTransaction($this->family, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'transfer',
            'amount' => 100,
            'date' => now()->format('Y-m-d'),
            'transfer_to_account_id' => $this->account->id,
        ]);
    }

    public function test_approving_transaction_with_insufficient_balance_throws(): void
    {
        $transaction = $this->family->transactions()->create([
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'amount' => 99999, // more than the 10,000 balance
            'date' => now(),
            'status' => 'pending',
            'needs_approval' => true,
        ]);

        $this->expectException(InsufficientBalanceException::class);

        $this->transactionService->approveTransaction($transaction);
    }

    public function test_updating_amount_rebalances_account(): void
    {
        $transaction = $this->transactionService->createTransaction($this->family, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 1000,
            'date' => now()->format('Y-m-d'),
        ]);

        $this->assertEquals(9000, (float) $this->account->fresh()->balance);

        $this->transactionService->updateTransaction($transaction, ['amount' => 400]);

        // Old 1,000 reverted, new 400 applied.
        $this->assertEquals(9600, (float) $this->account->fresh()->balance);
        $this->assertEquals(400, (float) $transaction->fresh()->amount);
    }

    public function test_update_does_not_drop_empty_description(): void
    {
        $transaction = $this->transactionService->createTransaction($this->family, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 100,
            'date' => now()->format('Y-m-d'),
            'description' => 'Original description',
        ]);

        // array_filter would silently drop '' — explicit key handling must not.
        $this->transactionService->updateTransaction($transaction, ['description' => '']);

        $this->assertSame('', (string) $transaction->fresh()->description);
    }

    public function test_deleting_approved_transaction_reverts_balance(): void
    {
        $transaction = $this->transactionService->createTransaction($this->family, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 750,
            'date' => now()->format('Y-m-d'),
        ]);

        $this->assertEquals(9250, (float) $this->account->fresh()->balance);

        $this->transactionService->deleteTransaction($transaction);

        $this->assertEquals(10000, (float) $this->account->fresh()->balance);
    }
}