<?php

namespace App\Repositories\Contracts;

interface ProductUsageRepositoryInterface
{
    public function countActiveProductsByTenant(int $tenantId): int;
}


