<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class ClearTenantCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-tenant {tenant_id? : The ID of the tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all cache for a specific tenant or all tenants';

    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');

        if ($tenantId) {
            $this->info("Clearing cache for tenant ID: {$tenantId}");
            $this->cacheService->invalidateAllTenantCache((int) $tenantId);
            $this->info("✓ Cache cleared successfully for tenant {$tenantId}!");
        } else {
            if ($this->confirm('Do you want to clear ALL cache?')) {
                $this->info('Clearing all cache...');
                $this->cacheService->clearAllCache();
                $this->info('✓ All cache cleared successfully!');
            } else {
                $this->warn('Operation cancelled');
            }
        }

        return 0;
    }
}

