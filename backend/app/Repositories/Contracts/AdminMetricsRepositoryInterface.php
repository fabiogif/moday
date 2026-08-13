<?php

namespace App\Repositories\Contracts;

interface AdminMetricsRepositoryInterface
{
    public function getTotalMrr(): float;

    public function getRevenueByMonth($startDate, $endDate): mixed;

    public function getRevenueByPlan(): mixed;

    public function getTopTenantsByMrr(int $limit = 10): mixed;

    public function getLoginsByDay($startDate, $endDate): mixed;

    public function getActiveTenantsPerDay($startDate, $endDate): mixed;

    public function getMostActiveTenants($startDate, $endDate, int $limit = 10): mixed;

    public function countInactiveTenants(): int;

    public function getAdoptionRate(): float;

    public function getMessagesByDay($startDate, $endDate): mixed;

    public function getMessageTotals($startDate, $endDate): mixed;

    public function getTopMessageSenders($startDate, $endDate, int $limit = 10): mixed;

    public function getTenantsNearMessageLimit(): mixed;

    public function getNewTenantsByMonth(int $months = 12): mixed;

    public function getConversionsByMonth(int $months = 12): mixed;

    public function getChurnByMonth(int $months = 12): mixed;

    public function getGrowthStats(): array;

    public function getTenantDailyMetrics(int $tenantId, $startDate, $endDate): mixed;

    public function getTenantAggregatedMetrics(int $tenantId, $startDate, $endDate): mixed;
}
