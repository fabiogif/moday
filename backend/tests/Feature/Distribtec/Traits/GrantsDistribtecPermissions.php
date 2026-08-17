<?php

namespace Tests\Feature\Distribtec\Traits;

use App\Models\Permission;
use App\Models\User;
use App\Support\AclPermissionDefinitions;
use Database\Seeders\DistribtecPermissionSeeder;

trait GrantsDistribtecPermissions
{
    protected function grantDistribtecPermissions(User $user, int $tenantId): void
    {
        (new DistribtecPermissionSeeder())->run($tenantId);

        // products.*/tables.*/clients.*/etc. (routes/api.php também exige acl.permission
        // nesses módulos) vivem em AclPermissionDefinitions, não no seeder acima.
        foreach (AclPermissionDefinitions::defaults() as $data) {
            Permission::firstOrCreate(
                ['slug' => $data['slug'], 'tenant_id' => $tenantId],
                array_merge($data, ['tenant_id' => $tenantId])
            );
        }

        $permissionIds = Permission::where('tenant_id', $tenantId)->pluck('id');
        $user->permissions()->syncWithoutDetaching($permissionIds);
    }
}
