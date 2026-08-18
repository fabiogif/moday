<?php

namespace App\Services;

use App\Models\Tenant;
use Database\Seeders\DefaultOrderStatusesSeeder;

class TenantOrderStatusProvisioner
{
    public function provision(Tenant $tenant): void
    {
        (new DefaultOrderStatusesSeeder())->seedForTenant((int) $tenant->id);
    }
}
