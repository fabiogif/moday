<?php

namespace App\Repositories\Contracts;

use App\Models\VisitRecurrence;
use Illuminate\Support\Collection;

interface VisitRecurrenceRepositoryInterface
{
    public function findForTenant(int $tenantId, string $uuid): ?VisitRecurrence;

    public function create(array $data): VisitRecurrence;

    public function update(VisitRecurrence $recurrence, array $data): VisitRecurrence;

    public function activeForTenant(int $tenantId): Collection;
}
