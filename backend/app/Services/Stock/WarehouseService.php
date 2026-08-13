<?php

namespace App\Services\Stock;

use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Support\Collection;

class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly CacheService $cacheService,
    ) {}

    public function list(int $tenantId): Collection
    {
        return $this->cacheService->getWarehouseList(
            $tenantId,
            fn () => $this->warehouseRepository->listForTenant($tenantId)
        );
    }

    public function find(int $tenantId, int $id): ?Warehouse
    {
        return $this->warehouseRepository->findForTenant($tenantId, $id);
    }

    public function create(int $tenantId, array $data): Warehouse
    {
        $warehouse = $this->warehouseRepository->create(array_merge($data, ['tenant_id' => $tenantId]));
        $this->cacheService->invalidateWarehouseCache($tenantId);

        return $warehouse;
    }

    public function update(int $tenantId, int $id, array $data): ?Warehouse
    {
        $warehouse = $this->warehouseRepository->findForTenant($tenantId, $id);
        if (!$warehouse) {
            return null;
        }

        $updated = $this->warehouseRepository->update($warehouse, $data);
        $this->cacheService->invalidateWarehouseCache($tenantId);

        return $updated;
    }

    public function deactivate(int $tenantId, int $id): ?Warehouse
    {
        $warehouse = $this->warehouseRepository->findForTenant($tenantId, $id);
        if (!$warehouse) {
            return null;
        }

        $deactivated = $this->warehouseRepository->deactivate($warehouse);
        $this->cacheService->invalidateWarehouseCache($tenantId);

        return $deactivated;
    }
}
