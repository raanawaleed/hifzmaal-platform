<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\MetalPriceFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MetalPriceFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('superadmin', 'web');
    }

    public function test_refresh_rates_returns_null_without_an_api_key(): void
    {
        config(['services.goldapi.key' => null]);

        $result = app(MetalPriceFeedService::class)->refreshRates('USD');

        $this->assertNull($result);
    }

    public function test_refresh_rates_stores_live_prices_in_platform_settings(): void
    {
        config(['services.goldapi.key' => 'test-key']);

        Http::fake([
            'https://www.goldapi.io/api/XAU/USD' => Http::response(['price_gram_24k' => 65.43], 200),
            'https://www.goldapi.io/api/XAG/USD' => Http::response(['price_gram_24k' => 0.85], 200),
        ]);

        $result = app(MetalPriceFeedService::class)->refreshRates('USD');

        $this->assertSame(65.43, $result['gold_per_gram']);
        $this->assertSame(0.85, $result['silver_per_gram']);
        $this->assertSame('USD', $result['currency']);

        $stored = PlatformSetting::get('zakat.metal_rates');
        $this->assertSame(65.43, $stored['gold_per_gram']);
    }

    public function test_refresh_rates_returns_null_when_provider_errors(): void
    {
        config(['services.goldapi.key' => 'test-key']);

        Http::fake([
            'https://www.goldapi.io/api/XAU/USD' => Http::response(['error' => 'rate limited'], 429),
            'https://www.goldapi.io/api/XAG/USD' => Http::response(['price_gram_24k' => 0.85], 200),
        ]);

        $result = app(MetalPriceFeedService::class)->refreshRates('USD');

        $this->assertNull($result);
    }

    public function test_superadmin_can_trigger_refresh_via_endpoint(): void
    {
        config(['services.goldapi.key' => 'test-key']);

        Http::fake([
            'https://www.goldapi.io/api/XAU/USD' => Http::response(['price_gram_24k' => 65.43], 200),
            'https://www.goldapi.io/api/XAG/USD' => Http::response(['price_gram_24k' => 0.85], 200),
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/settings/refresh-metal-rates')
            ->assertStatus(200)
            ->assertJsonPath('data.metal_rates.gold_per_gram', 65.43);
    }

    public function test_refresh_endpoint_returns_422_when_feed_unavailable(): void
    {
        config(['services.goldapi.key' => null]);

        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/settings/refresh-metal-rates')
            ->assertStatus(422)
            ->assertJson(['error' => 'metal_price_feed_unavailable']);
    }

    public function test_non_superadmin_cannot_trigger_refresh(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/admin/settings/refresh-metal-rates')
            ->assertStatus(403);
    }
}
