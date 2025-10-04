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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class ListingCacheTest extends TestCase
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

    public function test_client_listing_cache_works()
    {
        // Create some test clients
        Client::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);
        
        $clientService = app(ClientService::class);
        
        // First call should hit database
        $startTime = microtime(true);
        $clients1 = $clientService->getClientsByTenant($this->tenant->id);
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should hit cache
        $startTime = microtime(true);
        $clients2 = $clientService->getClientsByTenant($this->tenant->id);
        $secondCallTime = microtime(true) - $startTime;
        
        // Cache should be faster
        $this->assertLessThan($firstCallTime, $secondCallTime);
        $this->assertEquals($clients1->count(), $clients2->count());
        
        // Verify cache key exists
        $this->assertTrue(Cache::has("client_list_{$this->tenant->id}"));
    }

    public function test_product_listing_cache_works()
    {
        // Create some test products
        Product::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
        
        $productService = app(ProductService::class);
        
        // First call should hit database
        $startTime = microtime(true);
        $products1 = $productService->getProductsByTenantId($this->tenant->id);
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should hit cache
        $startTime = microtime(true);
        $products2 = $productService->getProductsByTenantId($this->tenant->id);
        $secondCallTime = microtime(true) - $startTime;
        
        // Cache should be faster
        $this->assertLessThan($firstCallTime, $secondCallTime);
        $this->assertEquals($products1->count(), $products2->count());
        
        // Verify cache key exists
        $this->assertTrue(Cache::has("product_list_{$this->tenant->id}"));
    }

    public function test_order_listing_cache_works()
    {
        // Create some test orders
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        Order::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id
        ]);
        
        $orderService = app(OrderService::class);
        
        // First call should hit database
        $startTime = microtime(true);
        $orders1 = $orderService->paginateByTenant($this->tenant->id, 1, 10);
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should hit cache
        $startTime = microtime(true);
        $orders2 = $orderService->paginateByTenant($this->tenant->id, 1, 10);
        $secondCallTime = microtime(true) - $startTime;
        
        // Cache should be faster
        $this->assertLessThan($firstCallTime, $secondCallTime);
        $this->assertEquals($orders1->total(), $orders2->total());
        
        // Verify cache key exists
        $this->assertTrue(Cache::has("order_list_{$this->tenant->id}"));
    }

    public function test_listing_cache_invalidation_works()
    {
        // Create initial data
        Client::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $clientService = app(ClientService::class);
        
        // Get clients to populate cache
        $clientService->getClientsByTenant($this->tenant->id);
        $this->assertTrue(Cache::has("client_list_{$this->tenant->id}"));
        
        // Create new client (should invalidate cache)
        $clientService->createClient([
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'tenant_id' => $this->tenant->id
        ]);
        
        // Cache should be invalidated
        $this->assertFalse(Cache::has("client_list_{$this->tenant->id}"));
    }

    public function test_listing_cache_with_different_parameters()
    {
        // Create test data
        Client::factory()->count(10)->create(['tenant_id' => $this->tenant->id]);
        
        $clientService = app(ClientService::class);
        
        // Get clients with different parameters
        $clients1 = $clientService->getClientsByTenant($this->tenant->id);
        $clients2 = $clientService->getClientsByTenant($this->tenant->id);
        
        // Should return same data
        $this->assertEquals($clients1->count(), $clients2->count());
        
        // Cache should exist
        $this->assertTrue(Cache::has("client_list_{$this->tenant->id}"));
    }

    public function test_listing_cache_performance_improvement()
    {
        // Create large dataset
        Client::factory()->count(100)->create(['tenant_id' => $this->tenant->id]);
        
        $clientService = app(ClientService::class);
        
        // Measure first call (database)
        $startTime = microtime(true);
        $clients1 = $clientService->getClientsByTenant($this->tenant->id);
        $dbTime = microtime(true) - $startTime;
        
        // Measure second call (cache)
        $startTime = microtime(true);
        $clients2 = $clientService->getClientsByTenant($this->tenant->id);
        $cacheTime = microtime(true) - $startTime;
        
        // Cache should be significantly faster
        $this->assertLessThan($dbTime, $cacheTime);
        
        // Performance improvement should be at least 50%
        $improvement = (($dbTime - $cacheTime) / $dbTime) * 100;
        $this->assertGreaterThan(50, $improvement);
        
        echo "\nPerformance improvement: " . round($improvement, 2) . "%\n";
        echo "Database time: " . round($dbTime * 1000, 2) . "ms\n";
        echo "Cache time: " . round($cacheTime * 1000, 2) . "ms\n";
    }

    public function test_listing_cache_ttl_configuration()
    {
        $cacheStats = $this->cacheService->getCacheStats();
        
        $this->assertArrayHasKey('cache_ttl', $cacheStats);
        $this->assertArrayHasKey('timestamp', $cacheStats);
        
        // Verify TTL values are reasonable
        $this->assertGreaterThan(0, $cacheStats['cache_ttl']['client_list']);
        $this->assertGreaterThan(0, $cacheStats['cache_ttl']['product_list']);
        $this->assertGreaterThan(0, $cacheStats['cache_ttl']['order_list']);
    }

    public function test_listing_cache_clear_all_works()
    {
        // Populate some cache
        $this->cacheService->getClientList($this->tenant->id, function() {
            return ['test' => 'data'];
        });
        
        $this->assertTrue(Cache::has("client_list_{$this->tenant->id}"));
        
        // Clear all cache
        $this->cacheService->clearAllCache();
        
        $this->assertFalse(Cache::has("client_list_{$this->tenant->id}"));
    }
}
