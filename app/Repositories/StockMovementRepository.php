<?php

namespace App\Repositories;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function paginateForTenant(int $tenantId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = StockMovement::forTenant($tenantId)
            ->with([
                // withTrashed: produto pode ter sido excluído (soft delete) depois
                // da movimentação — histórico ainda precisa mostrar o que era.
                'product' => fn ($q) => $q->withTrashed()->select('id', 'name', 'sku', 'unit_of_measure', 'deleted_at'),
                'batch:id,batch_number,manufacture_date,expiry_date,status',
                'warehouse:id,name',
                'performedBy:id,name',
            ])
            ->latest();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        return $query->paginate($perPage);
    }
}
