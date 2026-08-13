<?php

namespace App\Services\Stock;

use App\Models\Batch;
use App\Repositories\Contracts\BatchRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BatchQueryService
{
    public function __construct(
        private readonly BatchRepositoryInterface $batchRepository,
        private readonly CacheService $cacheService,
    ) {}

    public function list(int $tenantId, array $filters, int $perPage): LengthAwarePaginator
    {
        $params = array_merge($filters, ['per_page' => $perPage]);

        $paginated = $this->cacheService->getBatchList(
            $tenantId,
            $params,
            fn () => $this->batchRepository->paginateForTenant($tenantId, $filters, $perPage)
        );

        $paginated->getCollection()->transform(fn (Batch $b) => $this->withDays($b));

        return $paginated;
    }

    public function expiring(int $tenantId, int $days): Collection
    {
        return $this->batchRepository->findExpiring($tenantId, $days)
            ->map(fn (Batch $b) => $this->withDays($b));
    }

    public function expired(int $tenantId): Collection
    {
        return $this->batchRepository->findExpired($tenantId)
            ->map(fn (Batch $b) => $this->withDays($b));
    }

    public function find(int $tenantId, int $id, array $with = []): ?Batch
    {
        $batch = $this->findEntity($tenantId, $id, $with);

        return $batch ? $this->withDays($batch) : null;
    }

    public function findEntity(int $tenantId, int $id, array $with = []): ?Batch
    {
        return $this->batchRepository->findForTenant($tenantId, $id, $with);
    }

    public function withDays(Batch $batch): Batch
    {
        $batch->days_until_expiry = $batch->daysUntilExpiry();

        return $batch;
    }
}
