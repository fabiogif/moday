<?php

namespace App\Repositories\contracts;

use App\Models\Tenant;

interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    public function storeTenant(array $data);

    public function getTenantByUuid(string $uuid):Tenant|null;
}
