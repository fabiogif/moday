<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Services\CacheService;
use App\Services\ClientService;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;
    protected $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant and user
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $this->cacheService = app(CacheService::class);
    }

    public function test_client_stats_cache_works()
    {
        // Create some test data
        Client::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);
        
        $clientService = app(ClientService::class);
        
        // First call should hit database
        $startTime = microtime(true);
        $stats1 = $clientService->getClientStats($this->tenant->id);
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should hit cache
        $startTime = microtime(true);
        $stats2 = $clientService->getClientStats($this->tenant->id);
        $secondCallTime = microtime(true) - $startTime;
        
        // Cache should be faster
        $this->assertLessThan($firstCallTime, $secondCallTime);
        $this->assertEquals($stats1, $stats2);
        
        // Verify cache key exists
        $this->assertTrue(Cache::has("client_stats_{$this->tenant->id}"));
    }

    public function test_product_stats_cache_works()
    {
        // Create some test data
        Product::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
        
        $productService = app(ProductService::class);
        
        // First call should hit database
        $startTime = microtime(true);
        $stats1 = $productService->getStats($this->tenant->id);
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should hit cache
        $startTime = microtime(true);
        $stats2 = $productService->getStats($this->tenant->id);
        $secondCallTime = microtime(true) - $startTime;
        
        // Cache should be faster
        $this->assertLessThan($firstCallTime, $secondCallTime);
        $this->assertEquals($stats1, $stats2);
        
        // Verify cache key exists
        $this->assertTrue(Cache::has("product_stats_{$this->tenant->id}"));
    }

    public function test_order_stats_cache_works()
    {
        // Create some test data
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        Order::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id
        ]);
        
        $orderService = app(OrderService::class);
        
        // First call should hit database
        $startTime = microtime(true);
        $stats1 = $orderService->getOrderStats($this->tenant->id);
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should hit cache
        $startTime = microtime(true);
        $stats2 = $orderService->getOrderStats($this->tenant->id);
        $secondCallTime = microtime(true) - $startTime;
        
        // Cache should be faster
        $this->assertLessThan($firstCallTime, $secondCallTime);
        $this->assertEquals($stats1, $stats2);
        
        // Verify cache key exists
        $this->assertTrue(Cache::has("order_stats_{$this->tenant->id}"));
    }

    public function test_dashboard_cache_works()
    {
        // Create some test data
        Client::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);
        Product::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);
        
        $dashboardService = app(DashboardService::class);
        
        // First call should hit database
        $startTime = microtime(true);
        $data1 = $dashboardService->getDashboardData($this->tenant->id);
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should hit cache
        $startTime = microtime(true);
        $data2 = $dashboardService->getDashboardData($this->tenant->id);
        $secondCallTime = microtime(true) - $startTime;
        
        // Cache should be faster
        $this->assertLessThan($firstCallTime, $secondCallTime);
        $this->assertEquals($data1, $data2);
        
        // Verify cache key exists
        $this->assertTrue(Cache::has("dashboard_data_{$this->tenant->id}"));
    }

    public function test_cache_invalidation_works()
    {
        // Create initial data
        Client::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $clientService = app(ClientService::class);
        
        // Get stats to populate cache
        $clientService->getClientStats($this->tenant->id);
        $this->assertTrue(Cache::has("client_stats_{$this->tenant->id}"));
        
        // Create new client (should invalidate cache)
        $clientService->createClient([
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'tenant_id' => $this->tenant->id
        ]);
        
        // Cache should be invalidated
        $this->assertFalse(Cache::has("client_stats_{$this->tenant->id}"));
    }

    public function test_cache_ttl_configuration()
    {
        $cacheStats = $this->cacheService->getCacheStats();
        
        $this->assertArrayHasKey('cache_ttl', $cacheStats);
        $this->assertArrayHasKey('timestamp', $cacheStats);
        
        // Verify TTL values are reasonable
        $this->assertGreaterThan(0, $cacheStats['cache_ttl']['client_stats']);
        $this->assertGreaterThan(0, $cacheStats['cache_ttl']['product_stats']);
        $this->assertGreaterThan(0, $cacheStats['cache_ttl']['order_stats']);
    }

    public function test_cache_clear_all_works()
    {
        // Populate some cache
        $this->cacheService->getClientStats($this->tenant->id, function() {
            return ['test' => 'data'];
        });
        
        $this->assertTrue(Cache::has("client_stats_{$this->tenant->id}"));
        
        // Clear all cache
        $this->cacheService->clearAllCache();
        
        $this->assertFalse(Cache::has("client_stats_{$this->tenant->id}"));
    }
}
