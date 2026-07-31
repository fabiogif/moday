<?php

namespace App\Repositories;

use App\Models\SaleOrder;
use App\Repositories\Concerns\SearchesFullText;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SaleOrderRepository implements SaleOrderRepositoryInterface
{
    use SearchesFullText;

    public function paginateForTenant(int $tenantId, ?string $status, int $perPage, ?string $search = null, int $page = 1): LengthAwarePaginator
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
            $query->where(function ($q) use ($term, $search) {
                $q->where('identify', 'like', $term)
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $this->applyFullTextSearch(
                            $clientQuery,
                            ['name', 'company_name', 'trade_name'],
                            $search,
                            ['contact_name']
                        );
                    });
            });
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
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
