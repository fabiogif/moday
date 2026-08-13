<?php

namespace App\Services\Visit;

use App\Models\User;
use App\Repositories\Contracts\VisitReportRepositoryInterface;
use Carbon\Carbon;

class VisitReportService
{
    public function __construct(private readonly VisitReportRepositoryInterface $reportRepository)
    {
    }

    public function summary(int $tenantId, User $requestingUser, ?string $dateFrom, ?string $dateTo, int $days, ?int $userId): array
    {
        $end = $dateTo ? Carbon::parse($dateTo) : Carbon::today();
        $start = $dateFrom ? Carbon::parse($dateFrom) : $end->copy()->subDays($days - 1);

        $from = $start->format('Y-m-d');
        $to = $end->format('Y-m-d');

        $canViewAll = $requestingUser->hasPermissionTo('visits.view-all');
        $sellerFilter = $canViewAll ? $userId : null;

        $byStatus = $this->reportRepository->countsByStatus($tenantId, $from, $to, $requestingUser, $sellerFilter)
            ->mapWithKeys(fn ($row) => [$row->status => (int) $row->total]);

        $byType = $this->reportRepository->countsByType($tenantId, $from, $to, $requestingUser, $sellerFilter)
            ->mapWithKeys(fn ($row) => [$row->type => (int) $row->total]);

        $byPriority = $this->reportRepository->countsByPriority($tenantId, $from, $to, $requestingUser, $sellerFilter)
            ->mapWithKeys(fn ($row) => [$row->priority => (int) $row->total]);

        $totalVisits = $byStatus->sum();
        $completed = (int) $byStatus->get('concluida', 0);

        $bySellerRows = $canViewAll
            ? $this->reportRepository->countsBySeller($tenantId, $from, $to, $requestingUser)
            : collect();

        $salesClosed = 0;
        $totalOrderValue = 0.0;
        $bySeller = [];

        if ($canViewAll) {
            $userNames = User::query()->whereIn('id', $bySellerRows->pluck('user_id'))->pluck('name', 'id');

            foreach ($bySellerRows as $row) {
                $salesClosed += (int) $row->sales_closed;
                $totalOrderValue += (float) $row->order_value;

                $bySeller[] = [
                    'user_id' => (int) $row->user_id,
                    'user_name' => $userNames->get((int) $row->user_id),
                    'total_visits' => (int) $row->total,
                    'completed' => (int) $row->completed,
                    'sales_closed' => (int) $row->sales_closed,
                    'conversion_rate' => $row->completed > 0 ? round(($row->sales_closed / $row->completed) * 100, 2) : 0.0,
                    'order_value' => (float) $row->order_value,
                ];
            }
        } else {
            $ownRow = $this->reportRepository->countsBySeller($tenantId, $from, $to, $requestingUser)->first();
            if ($ownRow) {
                $salesClosed = (int) $ownRow->sales_closed;
                $totalOrderValue = (float) $ownRow->order_value;
            }
        }

        return [
            'period' => ['date_from' => $from, 'date_to' => $to],
            'total_visits' => $totalVisits,
            'by_status' => $byStatus,
            'by_type' => $byType,
            'by_priority' => $byPriority,
            'conversion' => [
                'completed' => $completed,
                'sales_closed' => $salesClosed,
                'conversion_rate' => $completed > 0 ? round(($salesClosed / $completed) * 100, 2) : 0.0,
                'total_order_value' => round($totalOrderValue, 2),
            ],
            'by_seller' => $canViewAll ? $bySeller : null,
        ];
    }
}
