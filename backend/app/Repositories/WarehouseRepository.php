<?php

namespace App\Repositories;

use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Support\Collection;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function listForTenant(int $tenantId): Collection
    {
        return Warehouse::forTenant($tenantId)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    public function findForTenant(int $tenantId, int $id): ?Warehouse
    {
        return Warehouse::forTenant($tenantId)->find($id);
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);

        return $warehouse->fresh();
    }

    public function deactivate(Warehouse $warehouse): Warehouse
    {
        $warehouse->update(['is_active' => false]);

        return $warehouse->fresh();
    }
}
