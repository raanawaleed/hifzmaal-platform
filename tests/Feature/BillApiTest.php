<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Category;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class BillApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    protected User $user;
    protected Family $family;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->family = $this->createFamilyFor($this->user);
        $this->category = Category::factory()->create([
            'family_id' => $this->family->id,
            'type' => 'expense',
        ]);
    }

    public function test_owner_can_create_bill(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/bills", [
                'category_id' => $this->category->id,
                'name' => 'Electricity',
                'type' => 'electricity',
                'amount' => 4500,
                'due_date' => now()->addDays(10)->toDateString(),
                'frequency' => 'monthly',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bills', [
            'family_id' => $this->family->id,
            'name' => 'Electricity',
        ]);
    }

    public function test_mark_as_paid_generates_next_recurring_bill(): void
    {
        $bill = Bill::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'is_recurring' => true,
            'frequency' => 'monthly',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/bills/{$bill->id}/mark-as-paid");

        $response->assertStatus(200);
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'status' => 'paid']);

        // A next-cycle bill must exist, exactly one.
        $this->assertEquals(
            1,
            Bill::where('family_id', $this->family->id)
                ->where('id', '!=', $bill->id)
                ->where('status', 'pending')
                ->count()
        );
    }

    public function test_mark_as_paid_is_idempotent(): void
    {
        $bill = Bill::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'is_recurring' => true,
            'frequency' => 'monthly',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/bills/{$bill->id}/mark-as-paid")
            ->assertStatus(200);

        // Second payment attempt: rejected, and no duplicate next bill.
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/bills/{$bill->id}/mark-as-paid")
            ->assertStatus(422);

        $this->assertEquals(2, Bill::where('family_id', $this->family->id)->count());
    }

    public function test_viewer_cannot_pay_bills(): void
    {
        $viewer = $this->addMember($this->family, 'viewer');

        $bill = Bill::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/families/{$this->family->id}/bills/{$bill->id}/mark-as-paid")
            ->assertStatus(403);
    }

    public function test_stranger_cannot_access_bills(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/bills")
            ->assertStatus(403);
    }

    public function test_can_get_upcoming_bills(): void
    {
        Bill::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/families/{$this->family->id}/bills/upcoming")
            ->assertStatus(200);
    }
}
