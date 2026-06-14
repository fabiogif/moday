<?php

namespace App\Repositories\Contracts;

use App\Models\Batch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BatchRepositoryInterface
{
    public function paginateForTenant(int $tenantId, array $filters, int $perPage): LengthAwarePaginator;

    public function findExpiring(int $tenantId, int $days): Collection;

    public function findExpired(int $tenantId): Collection;

    public function findForTenant(int $tenantId, int $id, array $with = []): ?Batch;
}
