<?php

namespace App\Repositories\Contracts;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PurchaseOrderRepositoryInterface
{
    public function paginateForTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator;

    public function findForTenant(int $tenantId, int $id, array $with = []): ?PurchaseOrder;

    public function create(array $data): PurchaseOrder;

    public function update(PurchaseOrder $order, array $data): PurchaseOrder;

    public function deleteWithItems(PurchaseOrder $order): void;
}
