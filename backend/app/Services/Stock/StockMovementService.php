<?php

namespace App\Services\Stock;

use App\Models\Batch;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementService
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly StockService $stockService,
        private readonly CacheService $cacheService,
    ) {}

    public function list(int $tenantId, array $filters, int $perPage): LengthAwarePaginator
    {
        $params = array_merge($filters, ['per_page' => $perPage]);

        return $this->cacheService->getStockMovementList(
            $tenantId,
            $params,
            fn () => $this->stockMovementRepository->paginateForTenant($tenantId, $filters, $perPage)
        );
    }

    /**
     * @return Batch|array{batch: Batch, movement_out: mixed, movement_in: mixed}
     */
    public function record(int $tenantId, int $userId, array $data): mixed
    {
        $result = match ($data['type']) {
            'entrada' => $this->stockService->recordEntry(
                $tenantId,
                (int) $data['product_id'],
                (int) $data['warehouse_id'],
                (float) $data['quantity'],
                $userId,
                [
                    'batch_number'     => $data['batch_number'] ?? null,
                    'manufacture_date' => $data['manufacture_date'] ?? null,
                    'expiry_date'      => $data['expiry_date'] ?? null,
                    'unit_cost'        => $data['unit_cost'] ?? null,
                    'notes'            => $data['notes'] ?? null,
                ],
            ),
            'ajuste' => $this->stockService->recordAdjustment(
                $tenantId,
                (int) $data['batch_id'],
                (float) $data['quantity'],
                $userId,
                $data['notes'] ?? null,
            ),
            'transferencia' => $this->stockService->recordTransfer(
                $tenantId,
                (int) $data['batch_id'],
                (int) $data['to_warehouse_id'],
                (float) $data['quantity'],
                $userId,
                $data['notes'] ?? null,
            ),
            'devolucao' => $this->stockService->recordReturn(
                $tenantId,
                (int) $data['product_id'],
                (int) $data['warehouse_id'],
                (float) $data['quantity'],
                $userId,
                [
                    'batch_number'     => $data['batch_number'] ?? null,
                    'manufacture_date' => $data['manufacture_date'] ?? null,
                    'expiry_date'      => $data['expiry_date'] ?? null,
                    'unit_cost'        => $data['unit_cost'] ?? null,
                    'notes'            => $data['notes'] ?? null,
                ],
            ),
        };

        $this->cacheService->invalidateStockMovementCache($tenantId);
        $this->cacheService->invalidateBatchCache($tenantId);

        return $result;
    }
}
