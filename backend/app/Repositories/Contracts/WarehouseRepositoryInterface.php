<?php

namespace App\Repositories\Contracts;

use App\Models\Warehouse;
use Illuminate\Support\Collection;

interface WarehouseRepositoryInterface
{
    public function listForTenant(int $tenantId): Collection;

    public function findForTenant(int $tenantId, int $id): ?Warehouse;

    public function create(array $data): Warehouse;

    public function update(Warehouse $warehouse, array $data): Warehouse;

    public function deactivate(Warehouse $warehouse): Warehouse;
}
