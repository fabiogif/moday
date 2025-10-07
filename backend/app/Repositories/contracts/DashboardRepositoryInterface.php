<?php

namespace App\Repositories\contracts;

interface DashboardRepositoryInterface
{
    public function getTotalRevenue(int $tenantId, string $startDate, string $endDate): float;
    
    public function getRevenueByPeriod(int $tenantId, string $startDate, string $groupBy = 'month'): array;
    
    public function getActiveClients(int $tenantId, string $startDate): int;
    
    public function getTotalOrders(int $tenantId, string $startDate, string $endDate): int;
    
    public function getConversionRate(int $tenantId, string $startDate): array;
    
    public function getSalesPerformance(int $tenantId, string $startDate): array;
    
    public function getRecentTransactions(int $tenantId, int $limit = 10): array;
    
    public function getTopProducts(int $tenantId, string $startDate, int $limit = 10): array;
}
