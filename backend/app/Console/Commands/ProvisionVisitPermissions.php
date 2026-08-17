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
 * - Administrador: todas as visits.*
 * - Gerente Comercial: agenda completa + view-all
 * - Vendedor: index/store/update + check-in/out/status/mídia
 *
 * Idempotente: seguro rodar mais de uma vez.
 */
class ProvisionVisitPermissions extends Command
{
    protected $signature = 'visits:provision-permissions {--dry-run : Apenas mostra o que seria alterado, sem gravar}';

    protected $description = 'Cria as permissões visits.* para todos os tenants existentes e concede aos profiles padrão';

    /** @var array<string, list<string>|string> */
    private const PROFILE_GRANTS = [
        'administrador' => '*',
        'gerente comercial' => [
            'visits.index', 'visits.store', 'visits.update', 'visits.destroy',
            'visits.checkin', 'visits.checkout', 'visits.change-status', 'visits.media.store',
            'visits.recurrence.manage', 'visits.reports.index', 'visits.view-all',
        ],
        'vendedor' => [
            'visits.index', 'visits.store', 'visits.update',
            'visits.checkin', 'visits.checkout', 'visits.change-status', 'visits.media.store',
        ],
    ];

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
            $permissionsBySlug = [];

            foreach ($visitDefinitions as $definition) {
                if ($dryRun) {
                    $permission = Permission::where('slug', $definition['slug'])->where('tenant_id', $tenantId)->first();
                    if (! $permission) {
                        $this->line("[dry-run] tenant={$tenantId}: criaria permission {$definition['slug']}");
                        continue;
                    }
                } else {
                    $permission = Permission::firstOrCreate(
                        ['slug' => $definition['slug'], 'tenant_id' => $tenantId],
                        array_merge($definition, ['tenant_id' => $tenantId, 'is_active' => true])
                    );
                }

                if ($permission) {
                    $permissionsBySlug[$permission->slug] = $permission->id;
                }
            }

            if ($permissionsBySlug === []) {
                continue;
            }

            foreach (self::PROFILE_GRANTS as $profileName => $grant) {
                $profile = Profile::where('tenant_id', $tenantId)
                    ->whereRaw('LOWER(name) = ?', [$profileName])
                    ->first();

                if (! $profile) {
                    $this->line("[skip] tenant={$tenantId}: profile \"{$profileName}\" não encontrado");
                    continue;
                }

                $permissionIds = $grant === '*'
                    ? array_values($permissionsBySlug)
                    : array_values(array_filter(array_map(
                        fn (string $slug) => $permissionsBySlug[$slug] ?? null,
                        $grant
                    )));

                if ($permissionIds === []) {
                    continue;
                }

                $existingIds = $profile->permissions()->pluck('permissions.id')->all();
                $missingIds = array_values(array_diff($permissionIds, $existingIds));

                if ($missingIds === []) {
                    continue;
                }

                $totalGranted += count($missingIds);
                $this->line(sprintf(
                    '%s tenant=%s profile="%s" (#%d): +%d permissões',
                    $dryRun ? '[dry-run]' : '[grant]',
                    $tenantId,
                    $profile->name,
                    $profile->id,
                    count($missingIds)
                ));

                if (! $dryRun) {
                    $profile->permissions()->attach($missingIds);
                }
            }
        }

        $this->info($dryRun
            ? "Dry-run concluído. {$totalGranted} concessões seriam feitas."
            : "Concluído. {$totalGranted} concessões gravadas."
        );

        return self::SUCCESS;
    }
}
