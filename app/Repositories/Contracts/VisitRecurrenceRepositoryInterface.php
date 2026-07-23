<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\VisitRecurrence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface VisitRecurrenceRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function listWithFilters(int $tenantId, array $filters, User $requestingUser, int $perPage = 50): LengthAwarePaginator;

    public function findForTenant(int $tenantId, string $uuid): ?VisitRecurrence;

    public function create(array $data): VisitRecurrence;

    public function update(VisitRecurrence $recurrence, array $data): VisitRecurrence;

    public function activeForTenant(int $tenantId): Collection;
}
