<?php

namespace App\Services;

use App\Repositories\Contracts\AdminMetricsRepositoryInterface;

readonly class AdminMetricsService
{
    public function __construct(
        private AdminMetricsRepositoryInterface $metricsRepository
    ) {}

    public function getRevenueMetrics($startDate, $endDate): array
    {
        return [
            'mrr' => $this->metricsRepository->getTotalMrr(),
            'revenue_by_month' => $this->metricsRepository->getRevenueByMonth($startDate, $endDate),
            'by_plan' => $this->metricsRepository->getRevenueByPlan(),
            'top_tenants' => $this->metricsRepository->getTopTenantsByMrr(),
        ];
    }

    public function getUsageMetrics($startDate, $endDate): array
    {
        return [
            'logins_by_day' => $this->metricsRepository->getLoginsByDay($startDate, $endDate),
            'active_tenants_per_day' => $this->metricsRepository->getActiveTenantsPerDay($startDate, $endDate),
            'most_active_tenants' => $this->metricsRepository->getMostActiveTenants($startDate, $endDate),
            'inactive_tenants_count' => $this->metricsRepository->countInactiveTenants(),
            'adoption_rate' => $this->metricsRepository->getAdoptionRate(),
        ];
    }

    public function getMessageMetrics($startDate, $endDate): array
    {
        $totals = $this->metricsRepository->getMessageTotals($startDate, $endDate);
        $whatsappCost = ($totals->whatsapp ?? 0) * 0.05;
        $smsCost = ($totals->sms ?? 0) * 0.30;

        return [
            'messages_by_day' => $this->metricsRepository->getMessagesByDay($startDate, $endDate),
            'totals' => [
                'total' => $totals->total ?? 0,
                'whatsapp' => $totals->whatsapp ?? 0,
                'email' => $totals->email ?? 0,
                'sms' => $totals->sms ?? 0,
            ],
            'costs' => [
                'whatsapp' => $whatsappCost,
                'sms' => $smsCost,
                'total' => $whatsappCost + $smsCost,
            ],
            'top_senders' => $this->metricsRepository->getTopMessageSenders($startDate, $endDate),
            'near_limit' => $this->metricsRepository->getTenantsNearMessageLimit(),
        ];
    }

    public function getGrowthMetrics(): array
    {
        $stats = $this->metricsRepository->getGrowthStats();

        return [
            'new_tenants_by_month' => $this->metricsRepository->getNewTenantsByMonth(),
            'conversions_by_month' => $this->metricsRepository->getConversionsByMonth(),
            'churn_by_month' => $this->metricsRepository->getChurnByMonth(),
            'conversion_rate' => $stats['conversion_rate'],
            'current_stats' => [
                'total_trials' => $stats['total_trials'],
                'total_active' => $stats['total_active'],
                'total_expired' => $stats['total_expired'],
            ],
        ];
    }

    public function getTenantMetrics(int $tenantId, $startDate, $endDate): array
    {
        return [
            'daily_metrics' => $this->metricsRepository->getTenantDailyMetrics($tenantId, $startDate, $endDate),
            'aggregated' => $this->metricsRepository->getTenantAggregatedMetrics($tenantId, $startDate, $endDate),
        ];
    }
}
