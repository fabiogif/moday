<?php

namespace App\Repositories\Contracts;

interface OrderUsageRepositoryInterface
{
    public function countOrdersForTenantInPeriod(int $tenantId, \DateTimeInterface $start, \DateTimeInterface $end): int;
}


