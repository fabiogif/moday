<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AclPermissionDefinitions;
use App\Support\DistribtecModulePermissionDefinitions;

class TenantAclProvisioner
{
    public const ADMIN_PROFILE_NAME = 'Administrador';

    /** Slugs usados nas rotas que podem faltar no tenant modelo. */
    private const ROUTE_ALIASES = [
        'users.create' => 'users.store',
        'users.delete' => 'users.destroy',
    ];

    public function provision(Tenant $tenant): Profile
    {
        $permissionIds = $this->ensurePermissions((int) $tenant->id);

        $profile = Profile::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => self::ADMIN_PROFILE_NAME],
            [
                'description' => 'Administrador do sistema com acesso total',
                'is_active' => true,
            ]
        );

        $profile->permissions()->sync($permissionIds);

        return $profile->fresh(['permissions']);
    }

    public function assignAdminProfile(User $user, Profile $adminProfile): void
    {
        $user->profiles()->sync([$adminProfile->id]);
        $user->clearPermissionsCache();
    }

    public function provisionAndAssignOwner(Tenant $tenant, User $owner): Profile
    {
        $profile = $this->provision($tenant);
        $this->assignAdminProfile($owner, $profile);

        app(JobPositionService::class)->seedDefaultsForTenant((int) $tenant->id);

        return $profile;
    }

    /**
     * @return int[]
     */
    private function ensurePermissions(int $tenantId): array
    {
        $definitions = $this->resolvePermissionDefinitions($tenantId);
        $bySlug = [];

        foreach ($definitions as $data) {
            $permission = Permission::firstOrCreate(
                ['slug' => $data['slug'], 'tenant_id' => $tenantId],
                array_merge($data, [
                    'tenant_id' => $tenantId,
                    'is_active' => $data['is_active'] ?? true,
                ])
            );
            $bySlug[$permission->slug] = $permission;
        }

        foreach (self::ROUTE_ALIASES as $aliasSlug => $sourceSlug) {
            if (isset($bySlug[$aliasSlug]) || !isset($bySlug[$sourceSlug])) {
                continue;
            }

            $source = $bySlug[$sourceSlug];
            $alias = Permission::firstOrCreate(
                ['slug' => $aliasSlug, 'tenant_id' => $tenantId],
                [
                    'name' => $source->name . ' (API)',
                    'description' => $source->description,
                    'module' => $source->module,
                    'action' => explode('.', $aliasSlug)[1] ?? $source->action,
                    'resource' => $source->resource,
                    'is_active' => true,
                    'tenant_id' => $tenantId,
                ]
            );
            $bySlug[$aliasSlug] = $alias;
        }

        return collect($bySlug)->pluck('id')->unique()->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolvePermissionDefinitions(int $tenantId): array
    {
        $templateTenantId = Permission::query()
            ->where('tenant_id', '!=', $tenantId)
            ->orderBy('tenant_id')
            ->value('tenant_id');

        $templateDefinitions = $templateTenantId
            ? Permission::where('tenant_id', $templateTenantId)
                ->get()
                ->map(fn (Permission $permission) => $permission->only([
                    'name',
                    'slug',
                    'description',
                    'module',
                    'action',
                    'resource',
                    'is_active',
                ]))
                ->all()
            : [];

        $definitions = $this->mergePermissionDefinitions(
            AclPermissionDefinitions::defaults(),
            $templateDefinitions
        );

        return $this->mergePermissionDefinitions(
            $definitions,
            DistribtecModulePermissionDefinitions::definitions()
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $base
     * @param  array<int, array<string, mixed>>  $extra
     * @return array<int, array<string, mixed>>
     */
    private function mergePermissionDefinitions(array $base, array $extra): array
    {
        $bySlug = collect($base)->keyBy('slug');

        foreach ($extra as $definition) {
            $bySlug->put($definition['slug'], $definition);
        }

        return $bySlug->values()->all();
    }
}
