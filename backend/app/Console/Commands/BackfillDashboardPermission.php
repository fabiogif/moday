<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AclPermissionDefinitions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillDashboardPermission extends Command
{
    protected $signature = 'acl:backfill-dashboard-permission
                            {--dry-run : Apenas mostra o que seria alterado, sem gravar}';

    protected $description = 'Cria dashboard.index e mantém o acesso dos perfis existentes ao dashboard';

    public function handle(): int
    {
        $definition = collect(AclPermissionDefinitions::defaults())
            ->firstWhere('slug', 'dashboard.index');

        if (!$definition) {
            $this->error('A definição dashboard.index não foi encontrada.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $totalGrants = 0;

        foreach (Tenant::query()->select('id')->cursor() as $tenant) {
            $tenantId = (int) $tenant->id;
            $profiles = Profile::query()->where('tenant_id', $tenantId)->get();
            $tenantGranted = false;
            $permission = Permission::query()
                ->where('tenant_id', $tenantId)
                ->where('slug', 'dashboard.index')
                ->first();

            if (!$permission && !$dryRun) {
                $permission = Permission::create(array_merge($definition, [
                    'tenant_id' => $tenantId,
                ]));
            }

            foreach ($profiles as $profile) {
                $alreadyGranted = $permission
                    ? $profile->permissions()->whereKey($permission->id)->exists()
                    : false;

                if ($alreadyGranted) {
                    continue;
                }

                $totalGrants++;
                $this->line(sprintf(
                    '%s tenant=%s profile="%s" (#%d)',
                    $dryRun ? '[dry-run]' : '[grant]',
                    $tenantId,
                    $profile->name,
                    $profile->id
                ));

                if (!$dryRun && $permission) {
                    DB::table('permission_profiles')->insertOrIgnore([
                        'profile_id' => $profile->id,
                        'permission_id' => $permission->id,
                    ]);
                    $tenantGranted = true;
                }
            }

            if ($tenantGranted) {
                User::query()
                    ->where('tenant_id', $tenantId)
                    ->eachById(fn (User $user) => $user->clearPermissionsCache());
            }
        }

        $this->info($dryRun
            ? "Dry-run concluído. {$totalGrants} concessões seriam feitas."
            : "Concluído. {$totalGrants} concessões gravadas."
        );

        return self::SUCCESS;
    }
}
