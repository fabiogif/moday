<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Profile;
use App\Support\AclPermissionDefinitions;
use App\Support\DistribtecModulePermissionDefinitions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * As rotas de /product, /table, /client e dos módulos financeiros (financial-categories,
 * suppliers, expenses, accounts-payable, accounts-receivable, bank-accounts, payment-methods)
 * passaram a exigir acl.permission, que antes não era verificado — qualquer usuário
 * autenticado do tenant podia usar esses endpoints independente do perfil.
 *
 * Para não bloquear usuários existentes no deploy, este comando concede essas permissões
 * (que já existem ou foram adicionadas ao catálogo, ver App\Support\AclPermissionDefinitions
 * e App\Support\DistribtecModulePermissionDefinitions) a todo profile que ainda não as
 * tenha — preservando o acesso irrestrito que já existia na prática. Perfis criados a
 * partir de agora podem ser configurados de forma mais granular manualmente pelo tenant.
 *
 * Idempotente: seguro rodar mais de uma vez. Rodar antes/durante o deploy desta correção.
 */
class BackfillCrudAclPermissions extends Command
{
    protected $signature = 'acl:backfill-crud-permissions {--dry-run : Apenas mostra o que seria alterado, sem gravar}';

    protected $description = 'Concede as permissões de módulos recém-protegidos por acl.permission a todos os profiles existentes (grandfather)';

    private const MODULES = [
        'products', 'tables', 'clients',
        'financial-categories', 'suppliers', 'expenses',
        'accounts-payable', 'accounts-receivable', 'bank-accounts', 'payment-methods',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $definitionsBySlug = collect(AclPermissionDefinitions::defaults())
            ->concat(DistribtecModulePermissionDefinitions::definitions())
            ->keyBy('slug');

        $slugs = $definitionsBySlug->keys()
            ->filter(fn (string $slug) => in_array(explode('.', $slug)[0], self::MODULES, true))
            ->values();

        $this->info('Slugs alvo: ' . $slugs->implode(', '));

        $profiles = Profile::with('permissions')->get();
        $this->info("Profiles encontrados: {$profiles->count()}");

        $totalGrants = 0;

        foreach ($profiles->groupBy('tenant_id') as $tenantId => $tenantProfiles) {
            // Garante que os Permission rows existem para este tenant (idempotente).
            $permissionIdsBySlug = [];
            foreach ($slugs as $slug) {
                $definition = $definitionsBySlug->get($slug);
                if (!$definition) {
                    continue;
                }

                if ($dryRun) {
                    $permission = Permission::where('slug', $slug)->where('tenant_id', $tenantId)->first();
                } else {
                    $permission = Permission::firstOrCreate(
                        ['slug' => $slug, 'tenant_id' => $tenantId],
                        array_merge($definition, ['tenant_id' => $tenantId, 'is_active' => $definition['is_active'] ?? true])
                    );
                }

                if ($permission) {
                    $permissionIdsBySlug[$slug] = $permission->id;
                }
            }

            if (empty($permissionIdsBySlug)) {
                continue;
            }

            foreach ($tenantProfiles as $profile) {
                $existingIds = $profile->permissions->pluck('id')->all();
                $missingIds = array_values(array_diff($permissionIdsBySlug, $existingIds));

                if (empty($missingIds)) {
                    continue;
                }

                $totalGrants += count($missingIds);
                $this->line(sprintf(
                    '%s tenant=%s profile="%s" (#%d): +%d permissões',
                    $dryRun ? '[dry-run]' : '[grant]',
                    $tenantId,
                    $profile->name,
                    $profile->id,
                    count($missingIds)
                ));

                if (!$dryRun) {
                    DB::table('permission_profiles')->insertOrIgnore(
                        collect($missingIds)->map(fn ($permissionId) => [
                            'profile_id' => $profile->id,
                            'permission_id' => $permissionId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])->all()
                    );
                }
            }
        }

        $this->info($dryRun
            ? "Dry-run concluído. {$totalGrants} concessões seriam feitas."
            : "Concluído. {$totalGrants} concessões gravadas."
        );

        return self::SUCCESS;
    }
}
