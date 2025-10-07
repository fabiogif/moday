<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CacheService
{
    /**
     * Cache TTL configurations (in seconds)
     */
    private const CACHE_TTL = [
        'client_stats' => 1800,      // 30 minutes
        'product_stats' => 1800,    // 30 minutes
        'order_stats' => 900,      // 15 minutes
        'category_stats' => 3600,   // 1 hour
        'table_stats' => 3600,     // 1 hour
        'payment_method_stats' => 3600, // 1 hour
        'order_data' => 600,      // 10 minutes
        'dashboard_data' => 1200,  // 20 minutes
        // Cache para listagens
        'client_list' => 900,       // 15 minutes
        'product_list' => 900,      // 15 minutes
        'order_list' => 600,       // 10 minutes
        'category_list' => 1800,     // 30 minutes
        'table_list' => 1800,       // 30 minutes
        'payment_method_list' => 1800, // 30 minutes
        'user_list' => 1200,        // 20 minutes
        'profile_list' => 3600,     // 1 hour
        'permission_list' => 7200, // 2 hours
        'role_list' => 3600,        // 1 hour
        // Novos caches para dashboard
        'dashboard_revenue' => 300,  // 5 minutes
        'dashboard_metrics' => 300,  // 5 minutes
        'sales_performance' => 600,  // 10 minutes
        'recent_transactions' => 300, // 5 minutes
        'top_products' => 600,       // 10 minutes
    ];

    /**
     * Get cached data or execute callback and cache result
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error("Cache error for key {$key}: " . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Get cached client statistics
     */
    public function getClientStats(int $tenantId, callable $callback)
    {
        $cacheKey = "client_stats_{$tenantId}";
        $ttl = self::CACHE_TTL['client_stats'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached product statistics
     */
    public function getProductStats(int $tenantId, callable $callback)
    {
        $cacheKey = "product_stats_{$tenantId}";
        $ttl = self::CACHE_TTL['product_stats'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached order statistics
     */
    public function getOrderStats(int $tenantId, callable $callback)
    {
        $cacheKey = "order_stats_{$tenantId}";
        $ttl = self::CACHE_TTL['order_stats'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached category statistics
     */
    public function getCategoryStats(int $tenantId, callable $callback)
    {
        $cacheKey = "category_stats_{$tenantId}";
        $ttl = self::CACHE_TTL['category_stats'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached table statistics
     */
    public function getTableStats(int $tenantId, callable $callback)
    {
        $cacheKey = "table_stats_{$tenantId}";
        $ttl = self::CACHE_TTL['table_stats'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached order data
     */
    public function getOrderData(int $tenantId, string $identifier, callable $callback)
    {
        $cacheKey = "order_data_{$tenantId}_{$identifier}";
        $ttl = self::CACHE_TTL['order_data'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached dashboard data
     */
    public function getDashboardData(int $tenantId, callable $callback)
    {
        $cacheKey = "dashboard_data_{$tenantId}";
        $ttl = self::CACHE_TTL['dashboard_data'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached client list
     */
    public function getClientList(int $tenantId, callable $callback)
    {
        $cacheKey = "client_list_{$tenantId}";
        $ttl = self::CACHE_TTL['client_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached product list
     */
    public function getProductList(int $tenantId, callable $callback)
    {
        $cacheKey = "product_list_{$tenantId}";
        $ttl = self::CACHE_TTL['product_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached order list
     */
    public function getOrderList(int $tenantId, callable $callback)
    {
        $cacheKey = "order_list_{$tenantId}";
        $ttl = self::CACHE_TTL['order_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached category list
     */
    public function getCategoryList(int $tenantId, callable $callback)
    {
        $cacheKey = "category_list_{$tenantId}";
        $ttl = self::CACHE_TTL['category_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached table list
     */
    public function getTableList(int $tenantId, callable $callback)
    {
        $cacheKey = "table_list_{$tenantId}";
        $ttl = self::CACHE_TTL['table_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached user list
     */
    public function getUserList(int $tenantId, callable $callback)
    {
        $cacheKey = "user_list_{$tenantId}";
        $ttl = self::CACHE_TTL['user_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached profile list
     */
    public function getProfileList(int $tenantId, callable $callback)
    {
        $cacheKey = "profile_list_{$tenantId}";
        $ttl = self::CACHE_TTL['profile_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached permission list
     */
    public function getPermissionList(int $tenantId, callable $callback)
    {
        $cacheKey = "permission_list_{$tenantId}";
        $ttl = self::CACHE_TTL['permission_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached role list
     */
    public function getRoleList(int $tenantId, callable $callback)
    {
        $cacheKey = "role_list_{$tenantId}";
        $ttl = self::CACHE_TTL['role_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached payment method list
     */
    public function getPaymentMethodList(int $tenantId, callable $callback)
    {
        $cacheKey = "payment_method_list_{$tenantId}";
        $ttl = self::CACHE_TTL['payment_method_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached active payment method list
     */
    public function getActivePaymentMethodList(int $tenantId, callable $callback)
    {
        $cacheKey = "active_payment_method_list_{$tenantId}";
        $ttl = self::CACHE_TTL['payment_method_list'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached payment method statistics
     */
    public function getPaymentMethodStats(int $tenantId, callable $callback)
    {
        $cacheKey = "payment_method_stats_{$tenantId}";
        $ttl = self::CACHE_TTL['payment_method_stats'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached dashboard revenue data
     */
    public function getDashboardRevenue(int $tenantId, callable $callback)
    {
        $cacheKey = "dashboard_revenue_{$tenantId}";
        $ttl = self::CACHE_TTL['dashboard_revenue'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached dashboard metrics
     */
    public function getDashboardMetrics(int $tenantId, callable $callback)
    {
        $cacheKey = "dashboard_metrics_{$tenantId}";
        $ttl = self::CACHE_TTL['dashboard_metrics'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached sales performance data
     */
    public function getSalesPerformance(int $tenantId, callable $callback)
    {
        $cacheKey = "sales_performance_{$tenantId}";
        $ttl = self::CACHE_TTL['sales_performance'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached recent transactions
     */
    public function getRecentTransactions(int $tenantId, callable $callback)
    {
        $cacheKey = "recent_transactions_{$tenantId}";
        $ttl = self::CACHE_TTL['recent_transactions'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached top products
     */
    public function getTopProducts(int $tenantId, callable $callback)
    {
        $cacheKey = "top_products_{$tenantId}";
        $ttl = self::CACHE_TTL['top_products'];
        
        return $this->remember($cacheKey, $ttl, $callback);
    }


    /**
     * Invalidate client-related cache
     */
    public function invalidateClientCache(int $tenantId): void
    {
        Cache::forget("client_stats_{$tenantId}");
        Cache::forget("client_list_{$tenantId}");
        Cache::forget("dashboard_data_{$tenantId}");
    }

    /**
     * Invalidate product-related cache
     */
    public function invalidateProductCache(int $tenantId): void
    {
        Cache::forget("product_stats_{$tenantId}");
        Cache::forget("product_list_{$tenantId}");
        Cache::forget("dashboard_data_{$tenantId}");
    }

    /**
     * Invalidate order-related cache
     */
    public function invalidateOrderCache(int $tenantId): void
    {
        Cache::forget("order_stats_{$tenantId}");
        Cache::forget("order_list_{$tenantId}");
        Cache::forget("dashboard_data_{$tenantId}");
        
        // Invalidate all order data cache for this tenant
        $this->invalidateOrderDataCache($tenantId);
    }

    /**
     * Invalidate category-related cache
     */
    public function invalidateCategoryCache(int $tenantId): void
    {
        Cache::forget("category_stats_{$tenantId}");
        Cache::forget("category_list_{$tenantId}");
        Cache::forget("dashboard_data_{$tenantId}");
    }

    /**
     * Invalidate table-related cache
     */
    public function invalidateTableCache(int $tenantId): void
    {
        Cache::forget("table_stats_{$tenantId}");
        Cache::forget("table_list_{$tenantId}");
        Cache::forget("dashboard_data_{$tenantId}");
    }

    /**
     * Invalidate payment method-related cache
     */
    public function invalidatePaymentMethodCache(int $tenantId): void
    {
        Cache::forget("payment_method_stats_{$tenantId}");
        Cache::forget("payment_method_list_{$tenantId}");
        Cache::forget("active_payment_method_list_{$tenantId}");
        Cache::forget("dashboard_data_{$tenantId}");
    }

    /**
     * Invalidate permission-related cache
     */
    public function invalidatePermissionCache(int $tenantId): void
    {
        Cache::forget("permission_list_{$tenantId}");
        Cache::forget("dashboard_data_{$tenantId}");
    }

    /**
     * Invalidate dashboard metrics cache
     */
    public function invalidateDashboardCache(int $tenantId): void
    {
        Cache::forget("dashboard_revenue_{$tenantId}");
        Cache::forget("dashboard_metrics_{$tenantId}");
        Cache::forget("sales_performance_{$tenantId}");
        Cache::forget("recent_transactions_{$tenantId}");
        Cache::forget("top_products_{$tenantId}");
        Cache::forget("dashboard_data_{$tenantId}");
    }


    /**
     * Invalidate all order data cache for a tenant
     */
    public function invalidateOrderDataCache(int $tenantId): void
    {
        // Get all cache keys that match the pattern
        $pattern = "order_data_{$tenantId}_*";
        
        // Note: This is a simplified approach. In production, you might want to use
        // Redis SCAN or maintain a list of cache keys
        $this->invalidateCacheByPattern($pattern);
    }

    /**
     * Invalidate all cache for a tenant
     */
    public function invalidateAllTenantCache(int $tenantId): void
    {
        $this->invalidateClientCache($tenantId);
        $this->invalidateProductCache($tenantId);
        $this->invalidateOrderCache($tenantId);
        $this->invalidateCategoryCache($tenantId);
        $this->invalidateTableCache($tenantId);
        $this->invalidatePaymentMethodCache($tenantId);
        $this->invalidatePermissionCache($tenantId);
        $this->invalidateDashboardCache($tenantId);
    }

    /**
     * Invalidate cache by pattern (Redis specific)
     */
    private function invalidateCacheByPattern(string $pattern): void
    {
        try {
            $store = Cache::getStore();
            
            // Check if we're using Redis store
            if (method_exists($store, 'getRedis')) {
                $redis = $store->getRedis();
                
                // Use SCAN instead of KEYS for better performance
                $cursor = '0';
                do {
                    $result = $redis->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);
                    $cursor = $result[0];
                    $keys = $result[1] ?? [];
                    
                    if (!empty($keys)) {
                        foreach ($keys as $key) {
                            Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                        }
                    }
                } while ($cursor !== '0');
            } else {
                // Fallback: For non-Redis stores, we can't use pattern matching
                Log::info("Cache pattern invalidation not supported for current driver");
            }
        } catch (\Exception $e) {
            Log::warning("Failed to invalidate cache pattern: {$pattern}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'cache_ttl' => self::CACHE_TTL,
            'timestamp' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Clear all cache
     */
    public function clearAllCache(): void
    {
        Cache::flush();
    }
}
