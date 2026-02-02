<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Family $family;
    protected Account $account;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->family = Family::factory()->create(['owner_id' => $this->user->id]);
        $this->account = Account::factory()->create([
            'family_id' => $this->family->id,
            'balance' => 10000,
        ]);
        $this->category = Category::factory()->create(['type' => 'expense']);
    }

    public function test_can_create_transaction(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions", [
                'account_id' => $this->account->id,
                'category_id' => $this->category->id,
                'type' => 'expense',
                'amount' => 500,
                'date' => now()->format('Y-m-d'),
                'description' => 'Test expense',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Transaction created successfully',
            ]);

        $this->assertDatabaseHas('transactions', [
            'family_id' => $this->family->id,
            'amount' => 500,
            'type' => 'expense',
        ]);
    }

    public function test_can_list_transactions(): void
    {
        Transaction::factory()->count(5)->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/transactions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'amount', 'date', 'description'],
                ],
            ]);
    }

    public function test_can_filter_transactions_by_type(): void
    {
        Transaction::factory()->count(3)->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
        ]);

        $incomeCategory = Category::factory()->create(['type' => 'income']);
        Transaction::factory()->count(2)->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $incomeCategory->id,
            'created_by' => $this->user->id,
            'type' => 'income',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/transactions?type=expense");

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }

    public function test_can_approve_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'status' => 'pending',
            'needs_approval' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions/{$transaction->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transaction approved successfully',
            ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'approved',
        ]);
    }

    public function test_can_get_pending_transactions(): void
    {
        Transaction::factory()->count(3)->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        Transaction::factory()->count(2)->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/transactions/pending");

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }
}