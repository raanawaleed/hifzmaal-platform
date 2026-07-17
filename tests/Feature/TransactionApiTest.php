<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    protected User $user;
    protected Family $family;
    protected Account $account;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->family = $this->createFamilyFor($this->user);
        $this->account = Account::factory()->create([
            'family_id' => $this->family->id,
            'balance' => 10000,
        ]);
        $this->category = Category::factory()->create([
            'family_id' => $this->family->id,
            'type' => 'expense',
        ]);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 500,
            'date' => now()->format('Y-m-d'),
            'description' => 'Test expense',
        ], $overrides);
    }

    public function test_can_create_transaction_and_balance_updates(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions", $this->payload());

        $response->assertStatus(201);

        $this->assertDatabaseHas('transactions', [
            'family_id' => $this->family->id,
            'amount' => 500,
            'type' => 'expense',
        ]);

        $this->assertEquals(9500, (float) $this->account->fresh()->balance);
    }

    public function test_expense_exceeding_balance_is_rejected(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions", $this->payload([
                'amount' => 999999,
            ]));

        $response->assertStatus(422)->assertJson(['error' => 'insufficient_balance']);
        $this->assertEquals(10000, (float) $this->account->fresh()->balance);
    }

    public function test_transfer_moves_balance_between_accounts(): void
    {
        $target = Account::factory()->create([
            'family_id' => $this->family->id,
            'balance' => 1000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions", $this->payload([
                'type' => 'transfer',
                'amount' => 2500,
                'transfer_to_account_id' => $target->id,
            ]));

        $response->assertStatus(201);
        $this->assertEquals(7500, (float) $this->account->fresh()->balance);
        $this->assertEquals(3500, (float) $target->fresh()->balance);
    }

    public function test_transfer_to_same_account_is_rejected(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions", $this->payload([
                'type' => 'transfer',
                'transfer_to_account_id' => $this->account->id,
            ]));

        $response->assertStatus(422)->assertJsonValidationErrors('transfer_to_account_id');
    }

    public function test_viewer_cannot_create_transaction(): void
    {
        $viewer = $this->addMember($this->family, 'viewer');

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions", $this->payload());

        $response->assertStatus(403);
    }

    public function test_cannot_access_another_familys_transactions(): void
    {
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/transactions");

        $response->assertStatus(403);
    }

    public function test_member_expense_above_spending_limit_needs_approval(): void
    {
        $member = $this->addMember($this->family, 'member', ['spending_limit' => 1000]);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions", $this->payload([
                'amount' => 1500,
            ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'family_id' => $this->family->id,
            'amount' => 1500,
            'status' => 'pending',
        ]);

        // Pending transactions must not move the balance.
        $this->assertEquals(10000, (float) $this->account->fresh()->balance);
    }

    public function test_owner_can_approve_pending_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'amount' => 500,
            'status' => 'pending',
            'needs_approval' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions/{$transaction->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'approved']);
        $this->assertEquals(9500, (float) $this->account->fresh()->balance);
    }

    public function test_member_cannot_approve_transactions(): void
    {
        $member = $this->addMember($this->family, 'member');

        $transaction = Transaction::factory()->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'status' => 'pending',
            'needs_approval' => true,
        ]);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions/{$transaction->id}/approve");

        $response->assertStatus(403);
    }

    public function test_deleting_approved_transaction_reverts_balance(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions", $this->payload());

        $transaction = Transaction::where('family_id', $this->family->id)->firstOrFail();
        $this->assertEquals(9500, (float) $this->account->fresh()->balance);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/families/{$this->family->id}/transactions/{$transaction->id}")
            ->assertStatus(200);

        $this->assertEquals(10000, (float) $this->account->fresh()->balance);
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
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
            ->assertJsonStructure(['data' => ['*' => ['id', 'type', 'amount', 'date']]]);
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

    public function test_validation_rejects_negative_amount(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions", $this->payload([
                'amount' => -50,
            ]));

        $response->assertStatus(422)->assertJsonValidationErrors('amount');
    }

    public function test_owner_can_upload_and_delete_a_receipt(): void
    {
        Storage::fake('public');

        $transaction = Transaction::factory()->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        // ->create() writes junk bytes finfo won't recognize as a real
        // image, which MediaLibrary's own content-sniffing then rejects —
        // ->image() renders an actual valid JPEG.
        $file = UploadedFile::fake()->image('receipt.jpg', 100, 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions/{$transaction->id}/receipts", [
                'receipt' => $file,
            ]);

        $response->assertStatus(201);
        $mediaId = $response->json('data.id');
        $this->assertNotNull($mediaId);
        $this->assertCount(1, $transaction->fresh()->getMedia('receipts'));

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/families/{$this->family->id}/transactions/{$transaction->id}/receipts/{$mediaId}")
            ->assertStatus(200);

        $this->assertCount(0, $transaction->fresh()->getMedia('receipts'));
    }

    public function test_receipt_upload_rejects_unsupported_file_type(): void
    {
        Storage::fake('public');

        $transaction = Transaction::factory()->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions/{$transaction->id}/receipts", [
                'receipt' => $file,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('receipt');
    }

    public function test_viewer_cannot_upload_a_receipt(): void
    {
        Storage::fake('public');

        $viewer = $this->addMember($this->family, 'viewer');
        $transaction = Transaction::factory()->create([
            'family_id' => $this->family->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'status' => 'approved',
        ]);

        $file = UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg');

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/transactions/{$transaction->id}/receipts", [
                'receipt' => $file,
            ])
            ->assertStatus(403);
    }
}
