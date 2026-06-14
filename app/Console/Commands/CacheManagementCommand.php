<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class CacheManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:manage 
                            {action : The action to perform (clear, stats, invalidate)}
                            {--tenant= : Tenant ID to invalidate cache for}
                            {--type= : Cache type to invalidate (client, product, order, category, table, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application cache (clear, stats, invalidate)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $cacheService = app(CacheService::class);

        switch ($action) {
            case 'clear':
                $this->clearCache($cacheService);
                break;
            case 'stats':
                $this->showCacheStats($cacheService);
                break;
            case 'invalidate':
                $this->invalidateCache($cacheService);
                break;
            default:
                $this->error('Invalid action. Use: clear, stats, or invalidate');
                return 1;
        }

        return 0;
    }

    private function clearCache(CacheService $cacheService)
    {
        $this->info('Clearing all cache...');
        $cacheService->clearAllCache();
        $this->info('✅ All cache cleared successfully!');
    }

    private function showCacheStats(CacheService $cacheService)
    {
        $this->info('📊 Cache Statistics:');
        $this->line('');
        
        $stats = $cacheService->getCacheStats();
        
        $this->table(
            ['Cache Type', 'TTL (minutes)'],
            [
                ['Client Stats', $stats['cache_ttl']['client_stats']],
                ['Product Stats', $stats['cache_ttl']['product_stats']],
                ['Order Stats', $stats['cache_ttl']['order_stats']],
                ['Category Stats', $stats['cache_ttl']['category_stats']],
                ['Table Stats', $stats['cache_ttl']['table_stats']],
                ['Order Data', $stats['cache_ttl']['order_data']],
                ['Dashboard Data', $stats['cache_ttl']['dashboard_data']],
                ['Client List', $stats['cache_ttl']['client_list']],
                ['Product List', $stats['cache_ttl']['product_list']],
                ['Order List', $stats['cache_ttl']['order_list']],
                ['Category List', $stats['cache_ttl']['category_list']],
                ['Table List', $stats['cache_ttl']['table_list']],
                ['User List', $stats['cache_ttl']['user_list']],
                ['Profile List', $stats['cache_ttl']['profile_list']],
                ['Permission List', $stats['cache_ttl']['permission_list']],
                ['Role List', $stats['cache_ttl']['role_list']],
            ]
        );
        
        $this->line('');
        $this->info("Last updated: {$stats['timestamp']}");
    }

    private function invalidateCache(CacheService $cacheService)
    {
        $tenantId = $this->option('tenant');
        $type = $this->option('type') ?? 'all';

        if (!$tenantId) {
            $this->error('❌ Tenant ID is required for cache invalidation');
            return;
        }

        $this->info("Invalidating {$type} cache for tenant {$tenantId}...");

        switch ($type) {
            case 'client':
                $cacheService->invalidateClientCache($tenantId);
                $this->info('✅ Client cache invalidated');
                break;
            case 'product':
                $cacheService->invalidateProductCache($tenantId);
                $this->info('✅ Product cache invalidated');
                break;
            case 'order':
                $cacheService->invalidateOrderCache($tenantId);
                $this->info('✅ Order cache invalidated');
                break;
            case 'category':
                $cacheService->invalidateCategoryCache($tenantId);
                $this->info('✅ Category cache invalidated');
                break;
            case 'table':
                $cacheService->invalidateTableCache($tenantId);
                $this->info('✅ Table cache invalidated');
                break;
            case 'all':
                $cacheService->invalidateAllTenantCache($tenantId);
                $this->info('✅ All tenant cache invalidated');
                break;
            default:
                $this->error('❌ Invalid cache type. Use: client, product, order, category, table, or all');
                return;
        }
    }
}
