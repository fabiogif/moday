<?php

namespace App\Repositories;

use App\Models\VisitRecurrence;
use App\Repositories\Contracts\VisitRecurrenceRepositoryInterface;
use Illuminate\Support\Collection;

class VisitRecurrenceRepository implements VisitRecurrenceRepositoryInterface
{
    public function findForTenant(int $tenantId, string $uuid): ?VisitRecurrence
    {
        return VisitRecurrence::query()->forTenant($tenantId)->where('uuid', $uuid)->first();
    }

    public function create(array $data): VisitRecurrence
    {
        return VisitRecurrence::create($data);
    }

    public function update(VisitRecurrence $recurrence, array $data): VisitRecurrence
    {
        $recurrence->update($data);

        return $recurrence->fresh();
    }

    public function activeForTenant(int $tenantId): Collection
    {
        return VisitRecurrence::query()->forTenant($tenantId)->where('is_active', true)->get();
    }
}
