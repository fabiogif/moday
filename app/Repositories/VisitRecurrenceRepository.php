<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\VisitRecurrence;
use App\Repositories\Contracts\VisitRecurrenceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VisitRecurrenceRepository implements VisitRecurrenceRepositoryInterface
{
    public function listWithFilters(int $tenantId, array $filters, User $requestingUser, int $perPage = 50): LengthAwarePaginator
    {
        $query = VisitRecurrence::query()
            ->forTenant($tenantId)
            ->with(['client:id,uuid,name,company_name,trade_name', 'user:id,name'])
            ->orderByDesc('is_active')
            ->orderBy('starts_on');

        if (!$requestingUser->hasPermissionTo('visits.view-all')) {
            $query->where('user_id', $requestingUser->id);
        } elseif (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', (int) $filters['client_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage);
    }

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
