<?php

namespace App\Repositories;

use App\Models\SaleOrder;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SaleOrderRepository implements SaleOrderRepositoryInterface
{
    public function paginateForTenant(int $tenantId, ?string $status, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $query = SaleOrder::forTenant($tenantId)
            ->notArchived()
            ->with(['client:id,name,company_name,trade_name,contact_name'])
            ->latest('ordered_at');

        if ($status) {
            $query->byStatus($status);
        }

        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('identify', 'like', $term)
                    ->orWhereHas('client', function ($clientQuery) use ($term) {
                        $clientQuery->where('name', 'like', $term)
                            ->orWhere('company_name', 'like', $term)
                            ->orWhere('trade_name', 'like', $term)
                            ->orWhere('contact_name', 'like', $term);
                    });
            });
        }

        return $query->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $id, array $with = []): ?SaleOrder
    {
        return SaleOrder::forTenant($tenantId)->with($with)->find($id);
    }

    public function findByOfflineId(int $tenantId, string $offlineId): ?SaleOrder
    {
        return SaleOrder::forTenant($tenantId)->where('offline_id', $offlineId)->first();
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
