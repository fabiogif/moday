<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\DistribtecModulePermissionDefinitions;
use Illuminate\Database\Seeder;

class DistribtecPermissionSeeder extends Seeder
{
    public function run(?int $tenantId = null): void
    {
        $tenantId ??= 1;

        foreach (DistribtecModulePermissionDefinitions::definitions() as $data) {
            Permission::firstOrCreate(
                ['slug' => $data['slug'], 'tenant_id' => $tenantId],
                array_merge($data, ['tenant_id' => $tenantId])
            );
        }
    }
}
