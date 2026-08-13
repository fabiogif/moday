<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderUsageRepositoryInterface;

class OrderUsageRepository implements OrderUsageRepositoryInterface
{
    public function __construct(
        private readonly Order $order,
    ) {}

    public function countOrdersForTenantInPeriod(int $tenantId, \DateTimeInterface $start, \DateTimeInterface $end): int
    {
        return $this->order->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }
}


