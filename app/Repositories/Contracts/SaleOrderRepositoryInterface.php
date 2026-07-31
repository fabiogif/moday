<?php

namespace App\Repositories\Contracts;

use App\Models\SaleOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SaleOrderRepositoryInterface
{
    public function paginateForTenant(int $tenantId, ?string $status, int $perPage, ?string $search = null, int $page = 1): LengthAwarePaginator;

    public function findForTenant(int $tenantId, int $id, array $with = []): ?SaleOrder;

    public function findByOfflineId(int $tenantId, string $offlineId): ?SaleOrder;

    public function create(array $data): SaleOrder;

    public function update(SaleOrder $order, array $data): SaleOrder;

    public function deleteWithItems(SaleOrder $order): void;
}
