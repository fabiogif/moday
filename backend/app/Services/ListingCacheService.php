<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ListingCacheService
{
    /**
     * Cache TTL configurations for listings (in minutes)
     */
    private const CACHE_TTL = [
        'client_list' => 15,       // 15 minutes
        'product_list' => 15,      // 15 minutes
        'order_list' => 10,       // 10 minutes
        'category_list' => 30,     // 30 minutes
        'table_list' => 30,       // 30 minutes
        'user_list' => 20,        // 20 minutes
        'profile_list' => 60,     // 1 hour
        'permission_list' => 120, // 2 hours
        'role_list' => 60,        // 1 hour
    ];

    /**
     * Get cached listing data with pagination support
     */
    public function getCachedListing(
        string $type,
        int $tenantId,
        array $params,
        callable $callback
    ) {
        $cacheKey = $this->generateCacheKey($type, $tenantId, $params);
        $ttl = self::CACHE_TTL[$type] ?? 15;
        
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Generate cache key for listing with parameters
     */
    private function generateCacheKey(string $type, int $tenantId, array $params): string
    {
        // Remove null values and sort parameters for consistent keys
        $cleanParams = array_filter($params, function($value) {
            return $value !== null && $value !== '';
        });
        
        ksort($cleanParams);
        
        $paramString = md5(serialize($cleanParams));
        return "{$type}_list_{$tenantId}_{$paramString}";
    }

    /**
     * Get cached client list
     */
    public function getClientList(int $tenantId, array $params, callable $callback)
    {
        return $this->getCachedListing('client_list', $tenantId, $params, $callback);
    }

    /**
     * Get cached product list
     */
    public function getProductList(int $tenantId, array $params, callable $callback)
    {
        return $this->getCachedListing('product_list', $tenantId, $params, $callback);
    }

    /**
     * Get cached order list
     */
    public function getOrderList(int $tenantId, array $params, callable $callback)
    {
        return $this->getCachedListing('order_list', $tenantId, $params, $callback);
    }

    /**
     * Get cached category list
     */
    public function getCategoryList(int $tenantId, array $params, callable $callback)
    {
        return $this->getCachedListing('category_list', $tenantId, $params, $callback);
    }

    /**
     * Get cached table list
     */
    public function getTableList(int $tenantId, array $params, callable $callback)
    {
        return $this->getCachedListing('table_list', $tenantId, $params, $callback);
    }

    /**
     * Get cached user list
     */
    public function getUserList(int $tenantId, array $params, callable $callback)
    {
        return $this->getCachedListing('user_list', $tenantId, $params, $callback);
    }

    /**
     * Get cached profile list
     */
    public function getProfileList(int $tenantId, array $params, callable $callback)
    {
        return $this->getCachedListing('profile_list', $tenantId, $params, $callback);
    }

    /**
     * Get cached permission list
     */
    public function getPermissionList(int $tenantId, array $params, callable $callback)
    {
        return $this->getCachedListing('permission_list', $tenantId, $params, $callback);
    }

    /**
     * Get cached role list
     */
    public function getRoleList(int $tenantId, array $params, callable $callback)
    {
        return $this->getCachedListing('role_list', $tenantId, $params, $callback);
    }

    /**
     * Invalidate listing cache for a specific type and tenant
     */
    public function invalidateListingCache(string $type, int $tenantId): void
    {
        $pattern = "{$type}_list_{$tenantId}_*";
        $this->invalidateCacheByPattern($pattern);
    }

    /**
     * Invalidate all listing cache for a tenant
     */
    public function invalidateAllListingCache(int $tenantId): void
    {
        $types = array_keys(self::CACHE_TTL);
        
        foreach ($types as $type) {
            $this->invalidateListingCache($type, $tenantId);
        }
    }

    /**
     * Invalidate cache by pattern (Redis specific)
     */
    private function invalidateCacheByPattern(string $pattern): void
    {
        try {
            // For now, use a simple approach - clear all cache
            // In production, you would implement pattern-based invalidation
            Log::info("Invalidating cache pattern: {$pattern}");
            Cache::flush();
        } catch (\Exception $e) {
            Log::warning("Failed to invalidate cache pattern: {$pattern}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics for listings
     */
    public function getCacheStats(): array
    {
        return [
            'cache_ttl' => self::CACHE_TTL,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Clear all listing cache
     */
    public function clearAllListingCache(): void
    {
        Cache::flush();
    }

    /**
     * Get cache TTL for a specific type
     */
    private function getCacheTTL(string $type): int
    {
        return self::CACHE_TTL[$type] ?? 15; // Default 15 minutes
    }

    /**
     * Remember paginated data with cache
     */
    public function rememberPaginated(string $key, string $type, callable $callback)
    {
        $ttl = $this->getCacheTTL($type);
        
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Invalidate user list cache
     */
    public function invalidateUserListCache(int $tenantId): void
    {
        $this->invalidateCacheByPattern("user_list_tenant_{$tenantId}_*");
    }
}
