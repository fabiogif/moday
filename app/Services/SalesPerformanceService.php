<?php

namespace App\Services;

use App\Repositories\Contracts\SalesPerformanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

readonly class SalesPerformanceService
{
    public function __construct(
        protected SalesPerformanceRepositoryInterface $salesPerformanceRepository
    ) {}

    /**
     * Get sales performance data for a period
     */
    public function getSalesPerformance(int $tenantId, ?string $startDate = null, ?string $endDate = null, int $days = 7): array
    {
        // Default to last 7 days if no dates provided
        if (!$startDate) {
            $endDate = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
            $startDate = Carbon::now()->subDays($days)->startOfDay()->format('Y-m-d H:i:s');
        } else {
            $startDate = Carbon::parse($startDate)->startOfDay()->format('Y-m-d H:i:s');
            $endDate = $endDate ? Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s') : Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        }

        // Calculate days difference for previous period
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $duration = $start->diffInDays($end);

        // Get current period data
        $currentPeriod = [
            'total_sales' => $this->salesPerformanceRepository->getTotalSales($tenantId, $startDate, $endDate),
            'total_sales_value' => $this->salesPerformanceRepository->getTotalSalesValue($tenantId, $startDate, $endDate),
            'average_ticket' => $this->salesPerformanceRepository->getAverageTicket($tenantId, $startDate, $endDate),
            'new_clients' => $this->salesPerformanceRepository->getNewClientsCount($tenantId, $startDate, $endDate),
        ];

        // Get previous period data
        $previousPeriod = $this->salesPerformanceRepository->getPreviousPeriodData($tenantId, $startDate, $endDate, $duration);

        // Calculate growth percentages
        $growth = $this->calculateGrowth($currentPeriod, $previousPeriod);

        // Get sales by hour
        $salesByHour = $this->salesPerformanceRepository->getSalesByHour($tenantId, $startDate, $endDate);
        $bestHour = $this->getBestHour($salesByHour);

        // Get sales by day of week
        $salesByDayOfWeek = $this->salesPerformanceRepository->getSalesByDayOfWeek($tenantId, $startDate, $endDate);
        $bestDay = $this->getBestDay($salesByDayOfWeek);

        // Get sales by payment method
        $salesByPaymentMethod = $this->salesPerformanceRepository->getSalesByPaymentMethod($tenantId, $startDate, $endDate);

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $duration,
            ],
            'indicators' => [
                'total_sales' => [
                    'current' => $currentPeriod['total_sales'],
                    'previous' => $previousPeriod['total_sales'],
                    'growth' => $growth['total_sales'],
                ],
                'total_sales_value' => [
                    'current' => round($currentPeriod['total_sales_value'], 2),
                    'previous' => round($previousPeriod['total_sales_value'], 2),
                    'growth' => $growth['total_sales_value'],
                ],
                'average_ticket' => [
                    'current' => round($currentPeriod['average_ticket'], 2),
                    'previous' => round($previousPeriod['average_ticket'], 2),
                    'growth' => $growth['average_ticket'],
                ],
                'new_clients' => [
                    'current' => $currentPeriod['new_clients'],
                    'previous' => $previousPeriod['new_clients'],
                    'growth' => $growth['new_clients'],
                ],
            ],
            'sales_by_hour' => $salesByHour,
            'best_hour' => $bestHour,
            'sales_by_day' => $salesByDayOfWeek,
            'best_day' => $bestDay,
            'sales_by_payment_method' => $salesByPaymentMethod,
        ];
    }

    /**
     * Calculate growth percentages
     */
    private function calculateGrowth(array $current, array $previous): array
    {
        $growth = [];

        $growth['total_sales'] = $previous['total_sales'] > 0
            ? round((($current['total_sales'] - $previous['total_sales']) / $previous['total_sales']) * 100, 2)
            : ($current['total_sales'] > 0 ? 100 : 0);

        $growth['total_sales_value'] = $previous['total_sales_value'] > 0
            ? round((($current['total_sales_value'] - $previous['total_sales_value']) / $previous['total_sales_value']) * 100, 2)
            : ($current['total_sales_value'] > 0 ? 100 : 0);

        $growth['average_ticket'] = $previous['average_ticket'] > 0
            ? round((($current['average_ticket'] - $previous['average_ticket']) / $previous['average_ticket']) * 100, 2)
            : ($current['average_ticket'] > 0 ? 100 : 0);

        $growth['new_clients'] = $previous['new_clients'] > 0
            ? round((($current['new_clients'] - $previous['new_clients']) / $previous['new_clients']) * 100, 2)
            : ($current['new_clients'] > 0 ? 100 : 0);

        return $growth;
    }

    /**
     * Get best hour (hour with most sales)
     */
    private function getBestHour(array $salesByHour): ?array
    {
        if (empty($salesByHour)) {
            return null;
        }

        $bestHour = null;
        $maxCount = 0;

        foreach ($salesByHour as $hour) {
            if ($hour['count'] > $maxCount) {
                $maxCount = $hour['count'];
                $bestHour = $hour;
            }
        }

        return $bestHour;
    }

    /**
     * Get best day (day with most sales)
     */
    private function getBestDay(array $salesByDayOfWeek): ?array
    {
        if (empty($salesByDayOfWeek)) {
            return null;
        }

        $bestDay = null;
        $maxCount = 0;

        foreach ($salesByDayOfWeek as $day) {
            if ($day['count'] > $maxCount) {
                $maxCount = $day['count'];
                $bestDay = $day;
            }
        }

        return $bestDay;
    }

    /**
     * Export sales performance data
     */
    public function exportSalesPerformance(int $tenantId, ?string $startDate = null, ?string $endDate = null, int $days = 7): array
    {
        $data = $this->getSalesPerformance($tenantId, $startDate, $endDate, $days);

        // Add export metadata
        $data['exported_at'] = now()->toISOString();
        $data['tenant_id'] = $tenantId;

        return $data;
    }

    /**
     * Invalidate cache for sales performance
     */
    public function invalidateCache(int $tenantId): void
    {
        $cacheKey = "sales_performance_{$tenantId}";
        Cache::forget($cacheKey);
    }
}

