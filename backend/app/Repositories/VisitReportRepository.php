<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Visit;
use App\Repositories\Contracts\VisitReportRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VisitReportRepository implements VisitReportRepositoryInterface
{
    private function scopedQuery(int $tenantId, string $dateFrom, string $dateTo, User $requestingUser, ?int $userId = null): Builder
    {
        $query = Visit::query()
            ->forTenant($tenantId)
            ->whereDate('scheduled_date', '>=', $dateFrom)
            ->whereDate('scheduled_date', '<=', $dateTo);

        if (!$requestingUser->hasPermissionTo('visits.view-all')) {
            $query->where('user_id', $requestingUser->id);
        } elseif ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    public function countsByStatus(int $tenantId, string $dateFrom, string $dateTo, User $requestingUser, ?int $userId = null): Collection
    {
        return $this->scopedQuery($tenantId, $dateFrom, $dateTo, $requestingUser, $userId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get();
    }

    public function countsByType(int $tenantId, string $dateFrom, string $dateTo, User $requestingUser, ?int $userId = null): Collection
    {
        return $this->scopedQuery($tenantId, $dateFrom, $dateTo, $requestingUser, $userId)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->get();
    }

    public function countsByPriority(int $tenantId, string $dateFrom, string $dateTo, User $requestingUser, ?int $userId = null): Collection
    {
        return $this->scopedQuery($tenantId, $dateFrom, $dateTo, $requestingUser, $userId)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->get();
    }

    public function countsBySeller(int $tenantId, string $dateFrom, string $dateTo, User $requestingUser): Collection
    {
        return $this->scopedQuery($tenantId, $dateFrom, $dateTo, $requestingUser)
            ->selectRaw(
                "user_id, count(*) as total, " .
                "sum(case when status = 'concluida' then 1 else 0 end) as completed, " .
                "sum(case when status = 'concluida' and result = 'venda_realizada' then 1 else 0 end) as sales_closed, " .
                "coalesce(sum(case when status = 'concluida' then order_value else 0 end), 0) as order_value"
            )
            ->groupBy('user_id')
            ->get();
    }
}
