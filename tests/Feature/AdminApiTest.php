<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Family;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regular;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin', 'web');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('superadmin');

        $this->regular = User::factory()->create();
    }

    public function test_regular_users_cannot_access_admin_endpoints(): void
    {
        $endpoints = [
            ['get', '/api/admin/dashboard'],
            ['get', '/api/admin/users'],
            ['get', '/api/admin/families'],
            ['get', '/api/admin/categories'],
            ['get', '/api/admin/settings'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $this->actingAs($this->regular, 'sanctum')
                ->json($method, $uri)
                ->assertStatus(403);
        }
    }

    public function test_admin_dashboard_returns_platform_stats(): void
    {
        User::factory()->count(3)->create();
        Family::factory()->create(['owner_id' => $this->regular->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['totals' => ['users', 'families', 'transactions'], 'signups'],
            ]);

        $this->assertEquals(5, $response->json('data.totals.users'));
        $this->assertEquals(1, $response->json('data.totals.families'));
    }

    public function test_admin_can_search_users(): void
    {
        User::factory()->create(['name' => 'Findable Person', 'email' => 'findme@example.com']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/users?search=findme');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_admin_can_suspend_and_unsuspend_user(): void
    {
        $victim = User::factory()->create();
        $victim->createToken('auth-token');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/users/{$victim->id}/suspend")
            ->assertStatus(200);

        $victim->refresh();
        $this->assertFalse($victim->is_active);
        $this->assertNotNull($victim->suspended_at);
        $this->assertEquals(0, $victim->tokens()->count());

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/users/{$victim->id}/unsuspend")
            ->assertStatus(200);

        $this->assertTrue($victim->fresh()->is_active);
    }

    public function test_admin_cannot_suspend_self_or_other_superadmins(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/users/{$this->admin->id}/suspend")
            ->assertStatus(422);

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('superadmin');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/users/{$otherAdmin->id}/suspend")
            ->assertStatus(422);
    }

    public function test_admin_can_list_and_delete_families(): void
    {
        $family = Family::factory()->create(['owner_id' => $this->regular->id]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/families')
            ->assertStatus(200);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/families/{$family->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $family->id);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/families/{$family->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('families', ['id' => $family->id]);
    }

    public function test_admin_can_manage_system_categories(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/categories', [
                'name' => 'Global Groceries',
                'type' => 'expense',
            ]);

        $response->assertStatus(201);

        $category = Category::where('name', 'Global Groceries')->firstOrFail();
        $this->assertTrue((bool) $category->is_system);
        $this->assertNull($category->family_id);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/categories/{$category->id}", ['name' => 'Global Food'])
            ->assertStatus(200);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_edit_family_owned_categories(): void
    {
        $family = Family::factory()->create(['owner_id' => $this->regular->id]);
        $category = Category::factory()->create(['family_id' => $family->id]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/categories/{$category->id}", ['name' => 'Hijacked'])
            ->assertStatus(404);
    }

    public function test_admin_can_update_zakat_settings(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/admin/settings', [
                'metal_rates' => [
                    'currency' => 'PKR',
                    'gold_per_gram' => 21000,
                    'silver_per_gram' => 260,
                ],
                'nisab' => [
                    'gold_grams' => 87.48,
                    'silver_grams' => 612.36,
                ],
            ]);

        $response->assertStatus(200);

        $rates = PlatformSetting::get('zakat.metal_rates');
        $this->assertEquals(21000, $rates['gold_per_gram']);
        $this->assertEquals($this->admin->id, $rates['updated_by']);
    }

    public function test_settings_update_validates_rates(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/admin/settings', [
                'metal_rates' => [
                    'currency' => 'PKR',
                    'gold_per_gram' => -5,
                    'silver_per_gram' => 260,
                ],
            ])
            ->assertStatus(422);
    }
}
