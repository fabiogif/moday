<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CacheService
{
    /**
     * Cache TTL configurations (in minutes)
     */
    private const CACHE_TTL = [
        'client_stats' => 30,      // 30 minutes
        'product_stats' => 30,    // 30 minutes
        'order_stats' => 15,      // 15 minutes
        'category_stats' => 60,   // 1 hour
        'table_stats' => 60,     // 1 hour
        'payment_method_stats' => 60, // 1 hour
        'order_data' => 10,      // 10 minutes
        'dashboard_data' => 20,  // 20 minutes
        // Cache para listagens
        'client_list' => 15,       // 15 minutes
        'product_list' => 15,      // 15 minutes
        'order_list' => 10,       // 10 minutes
        'category_list' => 30,     // 30 minutes
        'table_list' => 30,       // 30 minutes
        'payment_method_list' => 30, // 30 minutes
        'user_list' => 20,        // 20 minutes
        'profile_list' => 60,     // 1 hour
        'permission_list' => 120, // 2 hours
        'role_list' => 60,        // 1 hour
    ];

    /**
     * Get cached data or execute callback and cache result
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
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
    }

    /**
     * Invalidate cache by pattern (Redis specific)
     */
    private function invalidateCacheByPattern(string $pattern): void
    {
        // This is a simplified implementation
        // In production, you should use Redis SCAN command
        // or maintain a registry of cache keys
        
        try {
            // For Redis store, try to get Redis connection
            $store = Cache::getStore();
            if (method_exists($store, 'getRedis') && $store->getRedis()) {
                $redis = $store->getRedis();
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } else {
                // Fallback: clear all cache if pattern matching is not available
                Log::info("Cache pattern invalidation not supported, clearing all cache");
                Cache::flush();
            }
        } catch (\Exception $e) {
            // Log error but don't throw exception
            Log::warning("Failed to invalidate cache pattern: {$pattern}", [
                'error' => $e->getMessage()
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
