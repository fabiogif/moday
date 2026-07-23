<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\Tenant;
use App\Support\DistribtecModulePermissionDefinitions;
use Illuminate\Console\Command;

/**
 * O módulo de Agenda de Visitas é funcionalidade nova — diferente do backfill de
 * segurança (App\Console\Commands\BackfillCrudAclPermissions, que "regrandfatheia"
 * acesso que já era irrestrito antes de virar uma correção), aqui não existia
 * acesso nenhum antes, então o padrão correto é menor privilégio por padrão:
 * só o profile "Administrador" de cada tenant recebe as novas permissões
 * automaticamente (mesmo comportamento de App\Services\TenantAclProvisioner
 * para tenants novos). Perfis "Vendedor"/"Supervisor" devem ser configurados
 * manualmente pelo tenant na tela de Perfis, escolhendo as permissões visits.*
 * adequadas a cada papel.
 *
 * Idempotente: seguro rodar mais de uma vez.
 */
class ProvisionVisitPermissions extends Command
{
    protected $signature = 'visits:provision-permissions {--dry-run : Apenas mostra o que seria alterado, sem gravar}';

    protected $description = 'Cria as permissões visits.* para todos os tenants existentes e concede ao profile Administrador de cada um';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $visitDefinitions = collect(DistribtecModulePermissionDefinitions::definitions())
            ->filter(fn (array $definition) => $definition['module'] === 'visits')
            ->values();

        if ($visitDefinitions->isEmpty()) {
            $this->error('Nenhuma definição de permissão do módulo "visits" encontrada.');

            return self::FAILURE;
        }

        $this->info('Slugs do módulo visits: ' . $visitDefinitions->pluck('slug')->implode(', '));

        $tenantIds = Tenant::query()->pluck('id');
        $this->info("Tenants encontrados: {$tenantIds->count()}");

        $totalGranted = 0;

        foreach ($tenantIds as $tenantId) {
            $permissionIds = [];

            foreach ($visitDefinitions as $definition) {
                if ($dryRun) {
                    $permission = Permission::where('slug', $definition['slug'])->where('tenant_id', $tenantId)->first();
                } else {
                    $permission = Permission::firstOrCreate(
                        ['slug' => $definition['slug'], 'tenant_id' => $tenantId],
                        array_merge($definition, ['tenant_id' => $tenantId, 'is_active' => true])
                    );
                }

                if ($permission) {
                    $permissionIds[] = $permission->id;
                }
            }

            if ($permissionIds === []) {
                continue;
            }

            $adminProfile = Profile::where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', ['administrador'])
                ->first();

            if (!$adminProfile) {
                $this->line("[skip] tenant={$tenantId}: profile \"Administrador\" não encontrado");
                continue;
            }

            $existingIds = $adminProfile->permissions()->pluck('permissions.id')->all();
            $missingIds = array_values(array_diff($permissionIds, $existingIds));

            if ($missingIds === []) {
                continue;
            }

            $totalGranted += count($missingIds);
            $this->line(sprintf(
                '%s tenant=%s profile="%s" (#%d): +%d permissões',
                $dryRun ? '[dry-run]' : '[grant]',
                $tenantId,
                $adminProfile->name,
                $adminProfile->id,
                count($missingIds)
            ));

            if (!$dryRun) {
                $adminProfile->permissions()->attach($missingIds);
            }
        }

        $this->info($dryRun
            ? "Dry-run concluído. {$totalGranted} concessões seriam feitas."
            : "Concluído. {$totalGranted} concessões gravadas."
        );

        return self::SUCCESS;
    }
}
