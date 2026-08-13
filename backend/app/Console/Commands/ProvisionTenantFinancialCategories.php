<?php

namespace App\Console\Commands;

use App\Models\FinancialCategory;
use App\Models\Tenant;
use App\Services\TenantFinancialCategoryProvisioner;
use Illuminate\Console\Command;

class ProvisionTenantFinancialCategories extends Command
{
    protected $signature = 'tenants:provision-financial-categories {tenant? : ID do tenant (omitir para todos sem categorias)}';

    protected $description = 'Cria categorias financeiras padrão para tenant(s) que ainda não possuem';

    public function handle(TenantFinancialCategoryProvisioner $provisioner): int
    {
        $tenantId = $this->argument('tenant');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::query()
                ->whereNotIn('id', FinancialCategory::query()->distinct()->pluck('tenant_id'))
                ->get();

        if ($tenants->isEmpty()) {
            $this->info('Nenhum tenant pendente de categorias financeiras.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $created = $provisioner->provision($tenant);
            $this->info("Tenant #{$tenant->id} ({$tenant->name}): {$created} categoria(s) criada(s)");
        }

        return self::SUCCESS;
    }
}
