<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductUsageRepositoryInterface;

class ProductUsageRepository implements ProductUsageRepositoryInterface
{
    public function __construct(
        private readonly Product $product,
    ) {}

    public function countActiveProductsByTenant(int $tenantId): int
    {
        return $this->product->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
    }
}


