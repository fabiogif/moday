<?php

namespace Tests\Feature\Distribtec\Traits;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\DistribtecPermissionSeeder;

trait GrantsDistribtecPermissions
{
    protected function grantDistribtecPermissions(User $user, int $tenantId): void
    {
        (new DistribtecPermissionSeeder())->run($tenantId);

        $permissionIds = Permission::where('tenant_id', $tenantId)->pluck('id');
        $user->permissions()->syncWithoutDetaching($permissionIds);
    }
}
