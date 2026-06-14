<?php

namespace App\Repositories\Contracts;

interface AdminDashboardRepositoryInterface
{
    public function getTenantStats(): array;

    public function getFinancialStats(): array;

    public function getUsageStats(): array;

    public function getGrowthStats(): array;

    public function calculateChurnRate(): float;

    public function getRecentActivities(int $limit = 20): mixed;

    public function countTrialsExpiringSoon(): int;

    public function countOverdueInvoices(): int;

    public function countTenantsNearMessageLimit(): int;

    public function countInactiveTenants(): int;
}
