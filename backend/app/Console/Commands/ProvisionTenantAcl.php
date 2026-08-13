<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantAclProvisioner;
use App\Services\TenantFinancialCategoryProvisioner;
use Illuminate\Console\Command;

class ProvisionTenantAcl extends Command
{
    protected $signature = 'tenants:provision-acl {tenant? : ID do tenant (omitir para todos sem permissões)}';

    protected $description = 'Cria permissões e perfil Administrador para tenant(s) e vincula ao primeiro usuário';

    public function handle(
        TenantAclProvisioner $provisioner,
        TenantFinancialCategoryProvisioner $categoryProvisioner,
    ): int
    {
        $tenantId = $this->argument('tenant');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::query()
                ->where(function ($query) {
                    $query->whereNotIn('id', Permission::query()->distinct()->pluck('tenant_id'))
                        ->orWhereHas('users', function ($userQuery) {
                            $userQuery->whereDoesntHave('profiles');
                        });
                })
                ->get();

        if ($tenants->isEmpty()) {
            $this->info('Nenhum tenant pendente de provisionamento ACL.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $profile = $provisioner->provision($tenant);
            $categoriesCreated = $categoryProvisioner->provision($tenant);
            $owner = User::where('tenant_id', $tenant->id)->orderBy('id')->first();

            if ($owner) {
                $provisioner->assignAdminProfile($owner, $profile);
                $this->info("Tenant #{$tenant->id} ({$tenant->name}): ACL provisionado e vinculado a {$owner->email} ({$categoriesCreated} categoria(s) financeira(s))");
            } else {
                $this->warn("Tenant #{$tenant->id} ({$tenant->name}): ACL provisionado, sem usuário para vincular ({$categoriesCreated} categoria(s) financeira(s))");
            }
        }

        return self::SUCCESS;
    }
}
