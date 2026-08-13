<?php

namespace App\Repositories\Contracts;

interface SalesPerformanceRepositoryInterface
{
    /**
     * Get total sales count for a period
     */
    public function getTotalSales(int $tenantId, string $startDate, string $endDate): int;

    /**
     * Get total sales value for a period
     */
    public function getTotalSalesValue(int $tenantId, string $startDate, string $endDate): float;

    /**
     * Get average ticket for a period
     */
    public function getAverageTicket(int $tenantId, string $startDate, string $endDate): float;

    /**
     * Get new clients count for a period
     */
    public function getNewClientsCount(int $tenantId, string $startDate, string $endDate): int;

    /**
     * Get sales by hour for a period
     */
    public function getSalesByHour(int $tenantId, string $startDate, string $endDate): array;

    /**
     * Get sales by day of week for a period
     */
    public function getSalesByDayOfWeek(int $tenantId, string $startDate, string $endDate): array;

    /**
     * Get sales by payment method for a period
     */
    public function getSalesByPaymentMethod(int $tenantId, string $startDate, string $endDate): array;

    /**
     * Get previous period comparison data
     */
    public function getPreviousPeriodData(int $tenantId, string $startDate, string $endDate, int $days): array;
}

