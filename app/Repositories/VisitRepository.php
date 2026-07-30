<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Visit;
use App\Repositories\Concerns\SearchesFullText;
use App\Repositories\Contracts\VisitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VisitRepository implements VisitRepositoryInterface
{
    use SearchesFullText;

    public function listWithFilters(int $tenantId, array $filters, User $requestingUser, int $perPage = 50): LengthAwarePaginator
    {
        $query = Visit::query()
            ->forTenant($tenantId)
            ->with(['client:id,uuid,name,company_name,trade_name,cnpj,phone,city,state', 'user:id,name'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_start_time');

        // Vendedor sem permissão de ver toda a agenda do tenant só enxerga as próprias visitas.
        if (!$requestingUser->hasPermissionTo('visits.view-all')) {
            $query->where('user_id', $requestingUser->id);
        } elseif (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('scheduled_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('scheduled_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : explode(',', (string) $filters['status']);
            $query->whereIn('status', array_map('trim', $statuses));
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', (int) $filters['client_id']);
        }

        if (!empty($filters['city']) || !empty($filters['region'])) {
            $query->whereHas('client', function ($clientQuery) use ($filters) {
                if (!empty($filters['city'])) {
                    $clientQuery->where('city', $filters['city']);
                }
                if (!empty($filters['region'])) {
                    $clientQuery->where('neighborhood', $filters['region']);
                }
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('client', function ($clientQuery) use ($search) {
                $this->applyFullTextSearch(
                    $clientQuery,
                    ['name', 'company_name', 'trade_name'],
                    $search,
                    ['phone', 'whatsapp', 'city', 'uuid', 'cnpj']
                );
            });
        }

        return $query->paginate($perPage);
    }

    public function findForTenant(int $tenantId, string $uuid, array $with = []): ?Visit
    {
        return Visit::query()->forTenant($tenantId)->with($with)->where('uuid', $uuid)->first();
    }

    public function findByClientRequestId(int $tenantId, string $clientRequestId): ?Visit
    {
        return Visit::query()->forTenant($tenantId)->where('client_request_id', $clientRequestId)->first();
    }

    public function create(array $data): Visit
    {
        return Visit::create($data);
    }

    public function update(Visit $visit, array $data): Visit
    {
        $visit->update($data);

        return $visit->fresh();
    }

    public function delete(Visit $visit): void
    {
        $visit->delete();
    }

    public function lockOverlappingForUser(int $tenantId, int $userId, string $date, ?int $excludeVisitId = null): Collection
    {
        $query = Visit::query()
            ->forTenant($tenantId)
            ->where('user_id', $userId)
            ->whereDate('scheduled_date', $date)
            ->whereNotIn('status', ['cancelada', 'reagendada']);

        if ($excludeVisitId !== null) {
            $query->where('id', '!=', $excludeVisitId);
        }

        return $query->lockForUpdate()->get();
    }

    public function scheduledDatesForRecurrence(int $tenantId, int $recurrenceId): array
    {
        return Visit::query()
            ->forTenant($tenantId)
            ->where('recurrence_id', $recurrenceId)
            ->whereNotIn('status', ['cancelada'])
            ->get()
            ->map(fn (Visit $visit) => $visit->scheduled_date->format('Y-m-d'))
            ->all();
    }
}
