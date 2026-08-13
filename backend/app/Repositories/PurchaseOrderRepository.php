<?php

namespace App\Repositories;

use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function paginateForTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator
    {
        $query = PurchaseOrder::forTenant($tenantId)
            ->with(['supplier:id,company_name,trade_name'])
            ->withCount('items')
            ->latest();

        if ($status) {
            $query->byStatus($status);
        }

        return $query->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $id, array $with = []): ?PurchaseOrder
    {
        return PurchaseOrder::forTenant($tenantId)->with($with)->find($id);
    }

    public function create(array $data): PurchaseOrder
    {
        return PurchaseOrder::create($data);
    }

    public function update(PurchaseOrder $order, array $data): PurchaseOrder
    {
        $order->update($data);

        return $order->fresh();
    }

    public function deleteWithItems(PurchaseOrder $order): void
    {
        $order->items()->delete();
        $order->delete();
    }
}
