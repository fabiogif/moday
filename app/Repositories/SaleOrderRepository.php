<?php

namespace App\Repositories;

use App\Models\SaleOrder;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SaleOrderRepository implements SaleOrderRepositoryInterface
{
    public function paginateForTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator
    {
        $query = SaleOrder::forTenant($tenantId)
            ->notArchived()
            ->with(['client:id,company_name,trade_name'])
            ->latest('ordered_at');

        if ($status) {
            $query->byStatus($status);
        }

        return $query->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $id, array $with = []): ?SaleOrder
    {
        return SaleOrder::forTenant($tenantId)->with($with)->find($id);
    }

    public function create(array $data): SaleOrder
    {
        return SaleOrder::create($data);
    }

    public function update(SaleOrder $order, array $data): SaleOrder
    {
        $order->update($data);

        return $order->fresh();
    }

    public function deleteWithItems(SaleOrder $order): void
    {
        $order->items()->delete();
        $order->delete();
    }
}
