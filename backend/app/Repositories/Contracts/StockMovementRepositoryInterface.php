<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockMovementRepositoryInterface
{
    public function paginateForTenant(int $tenantId, array $filters, int $perPage): LengthAwarePaginator;
}
