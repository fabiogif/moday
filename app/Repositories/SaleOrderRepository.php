<?php

namespace App\Repositories;

use App\Models\SaleOrder;
use App\Repositories\Concerns\SearchesFullText;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SaleOrderRepository implements SaleOrderRepositoryInterface
{
    use SearchesFullText;

    public function summaryForTenant(int $tenantId, ?Carbon $start, Carbon $end): array
    {
        $pending = SaleOrder::forTenant($tenantId)->notArchived()->where('status', 'orcamento')->count();

        $query = SaleOrder::forTenant($tenantId)->notArchived();
        if ($start) {
            $query->whereBetween('ordered_at', [$start, $end]);
        }
        $total = (clone $query)->count();

        $series = [];
        if ($start) {
            $counts = $query->selectRaw('DATE(ordered_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->pluck('count', 'day');

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $key = $cursor->format('Y-m-d');
                $series[] = ['date' => $key, 'count' => (int) ($counts[$key] ?? 0)];
            }
        }

        return ['pending' => $pending, 'total' => $total, 'series' => $series];
    }

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
