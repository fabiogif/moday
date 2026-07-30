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
        // DistribTec modules
        'supplier_list' => 600,          // 10 minutes
        'warehouse_list' => 1800,        // 30 minutes
        'batch_list' => 300,             // 5 minutes
        'sale_order_list' => 300,        // 5 minutes
        'purchase_order_list' => 300,    // 5 minutes
        'stock_movement_list' => 180,    // 3 minutes
        // Sales Goals & Gamification
        'sales_goal_list' => 300,        // 5 minutes
        'ranking_list' => 300,           // 5 minutes
        'gamification_profile' => 600,   // 10 minutes
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
        $v = (int) Cache::get("order_data_v_{$tenantId}", 0);
        $cacheKey = "order_data_{$tenantId}_v{$v}_{$identifier}";
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
     * Get cached paginated client list (search/paginação para telas com muitos clientes).
     */
    public function getClientListPaginated(int $tenantId, array $params, callable $callback)
    {
        $v = (int) Cache::get("client_list_v_{$tenantId}", 0);
        $key = "client_list_p_{$tenantId}_v{$v}_" . md5(json_encode($params));
        return $this->remember($key, self::CACHE_TTL['client_list'], $callback);
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
     * Lista de produtos visíveis no PDV / cardápio de venda.
     */
    public function getProductCatalogList(int $tenantId, callable $callback)
    {
        $cacheKey = "product_catalog_list_{$tenantId}";
        $ttl = self::CACHE_TTL['product_list'];

        return $this->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached order list
     */
    public function getOrderList(int $tenantId, callable $callback, array $params = [])
    {
        $v = (int) Cache::get("order_list_v_{$tenantId}", 0);
        $paramsKey = md5(json_encode($params));
        $cacheKey = "order_list_{$tenantId}_v{$v}_{$paramsKey}";
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
        Cache::increment("client_list_v_{$tenantId}");
        Cache::forget("dashboard_data_{$tenantId}");
    }

    /**
     * Invalidate product-related cache
     */
    public function invalidateProductCache(int $tenantId): void
    {
        Cache::forget("product_stats_{$tenantId}");
        Cache::forget("product_list_{$tenantId}");
        Cache::forget("product_catalog_list_{$tenantId}");
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
        
        // Invalidate all paginated order list cache with different parameters
        $this->invalidateOrderListCache($tenantId);
    }
    
    /**
     * Invalidate all order list cache variations (with different pagination params)
     */
    public function invalidateOrderListCache(int $tenantId): void
    {
        Cache::increment("order_list_v_{$tenantId}");
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
        Cache::increment("order_data_v_{$tenantId}");
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

            if (method_exists($store, 'getRedis')) {
                $redis  = $store->getRedis();
                $prefix = config('cache.prefix') . ':';

                $cursor = '0';
                do {
                    $result = $redis->scan($cursor, ['MATCH' => $prefix . $pattern, 'COUNT' => 100]);
                    $cursor = $result[0];
                    $keys   = $result[1] ?? [];

                    if (!empty($keys)) {
                        foreach ($keys as $key) {
                            Cache::forget(str_replace($prefix, '', $key));
                        }
                    }
                } while ($cursor !== '0');
            } else {
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

    // -------------------------------------------------------------------------
    // DistribTec module caches
    // -------------------------------------------------------------------------

    public function getSupplierList(int $tenantId, callable $callback)
    {
        return $this->remember("supplier_list_{$tenantId}", self::CACHE_TTL['supplier_list'], $callback);
    }

    public function getWarehouseList(int $tenantId, callable $callback)
    {
        return $this->remember("warehouse_list_{$tenantId}", self::CACHE_TTL['warehouse_list'], $callback);
    }

    public function getBatchList(int $tenantId, array $params, callable $callback)
    {
        $v = (int) Cache::get("batch_v_{$tenantId}", 0);
        $key = "batch_list_{$tenantId}_v{$v}_" . md5(json_encode($params));
        return $this->remember($key, self::CACHE_TTL['batch_list'], $callback);
    }

    public function getSaleOrderList(int $tenantId, array $params, callable $callback)
    {
        $v = (int) Cache::get("sale_order_v_{$tenantId}", 0);
        $key = "sale_order_list_{$tenantId}_v{$v}_" . md5(json_encode($params));
        return $this->remember($key, self::CACHE_TTL['sale_order_list'], $callback);
    }

    public function getPurchaseOrderList(int $tenantId, array $params, callable $callback)
    {
        $v = (int) Cache::get("purchase_order_v_{$tenantId}", 0);
        $key = "purchase_order_list_{$tenantId}_v{$v}_" . md5(json_encode($params));
        return $this->remember($key, self::CACHE_TTL['purchase_order_list'], $callback);
    }

    public function getStockMovementList(int $tenantId, array $params, callable $callback)
    {
        $v = (int) Cache::get("stock_movement_v_{$tenantId}", 0);
        $key = "stock_movement_list_{$tenantId}_v{$v}_" . md5(json_encode($params));
        return $this->remember($key, self::CACHE_TTL['stock_movement_list'], $callback);
    }

    public function invalidateSupplierCache(int $tenantId): void
    {
        Cache::forget("supplier_list_{$tenantId}");
    }

    public function invalidateWarehouseCache(int $tenantId): void
    {
        Cache::forget("warehouse_list_{$tenantId}");
    }

    public function invalidateBatchCache(int $tenantId): void
    {
        Cache::increment("batch_v_{$tenantId}");
    }

    public function invalidateSaleOrderCache(int $tenantId): void
    {
        Cache::increment("sale_order_v_{$tenantId}");
    }

    public function invalidatePurchaseOrderCache(int $tenantId): void
    {
        Cache::increment("purchase_order_v_{$tenantId}");
    }

    public function invalidateStockMovementCache(int $tenantId): void
    {
        Cache::increment("stock_movement_v_{$tenantId}");
    }

    // -------------------------------------------------------------------------
    // Sales Goals & Gamification caches
    // -------------------------------------------------------------------------

    public function getSalesGoalList(int $tenantId, array $params, callable $callback)
    {
        $v = (int) Cache::get("sales_goal_v_{$tenantId}", 0);
        $key = "sales_goal_list_{$tenantId}_v{$v}_" . md5(json_encode($params));
        return $this->remember($key, self::CACHE_TTL['sales_goal_list'], $callback);
    }

    public function getRankingList(int $tenantId, array $params, callable $callback)
    {
        $v = (int) Cache::get("ranking_v_{$tenantId}", 0);
        $key = "ranking_list_{$tenantId}_v{$v}_" . md5(json_encode($params));
        return $this->remember($key, self::CACHE_TTL['ranking_list'], $callback);
    }

    public function getGamificationProfile(int $tenantId, int $userId, callable $callback)
    {
        $key = "gamification_profile_{$tenantId}_{$userId}";
        return $this->remember($key, self::CACHE_TTL['gamification_profile'], $callback);
    }

    public function invalidateSalesGoalCache(int $tenantId): void
    {
        Cache::increment("sales_goal_v_{$tenantId}");
        Cache::increment("ranking_v_{$tenantId}");
    }

    public function invalidateGamificationCache(int $tenantId): void
    {
        Cache::increment("ranking_v_{$tenantId}");
        $this->invalidateCacheByPattern("gamification_profile_{$tenantId}_*");
    }
}
