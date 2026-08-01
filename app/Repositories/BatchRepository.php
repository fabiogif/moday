<?php

namespace App\Repositories;

use App\Models\Batch;
use App\Repositories\Contracts\BatchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BatchRepository implements BatchRepositoryInterface
{
    public function paginateForTenant(int $tenantId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Batch::forTenant($tenantId)
            ->with([
                // withTrashed: produto pode ter sido excluído (soft delete) depois
                // do lote existir — histórico ainda precisa mostrar o que era.
                'product' => fn ($q) => $q->withTrashed()->select('id', 'name', 'sku', 'unit_of_measure', 'deleted_at'),
                'warehouse:id,name',
            ])
            ->orderBy('expiry_date', 'asc');

        if (!empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function findExpiring(int $tenantId, int $days): Collection
    {
        return Batch::forTenant($tenantId)
            ->expiringSoon($days)
            ->with([
                // withTrashed: produto pode ter sido excluído (soft delete) depois
                // do lote existir — histórico ainda precisa mostrar o que era.
                'product' => fn ($q) => $q->withTrashed()->select('id', 'name', 'sku', 'unit_of_measure', 'deleted_at'),
                'warehouse:id,name',
            ])
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    public function findExpired(int $tenantId): Collection
    {
        return Batch::forTenant($tenantId)
            ->expired()
            ->with([
                // withTrashed: produto pode ter sido excluído (soft delete) depois
                // do lote existir — histórico ainda precisa mostrar o que era.
                'product' => fn ($q) => $q->withTrashed()->select('id', 'name', 'sku', 'unit_of_measure', 'deleted_at'),
                'warehouse:id,name',
            ])
            ->orderBy('expiry_date', 'desc')
            ->get();
    }

    public function findForTenant(int $tenantId, int $id, array $with = []): ?Batch
    {
        return Batch::forTenant($tenantId)->with($with)->find($id);
    }
}
