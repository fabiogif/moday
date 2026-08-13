<?php

namespace App\Services;

use App\Repositories\Contracts\AdminDashboardRepositoryInterface;

readonly class AdminDashboardService
{
    public function __construct(
        private AdminDashboardRepositoryInterface $dashboardRepository
    ) {}

    public function getDashboardStats(): array
    {
        return [
            'tenants' => $this->dashboardRepository->getTenantStats(),
            'financial' => $this->dashboardRepository->getFinancialStats(),
            'usage' => $this->dashboardRepository->getUsageStats(),
            'growth' => $this->dashboardRepository->getGrowthStats(),
        ];
    }

    public function getRecentActivity(int $limit = 20): mixed
    {
        return $this->dashboardRepository->getRecentActivities($limit);
    }

    public function getAlerts(): array
    {
        $alerts = [];

        $trialsExpiringSoon = $this->dashboardRepository->countTrialsExpiringSoon();
        if ($trialsExpiringSoon > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$trialsExpiringSoon} empresa(s) com trial expirando em 3 dias",
                'action' => 'view_expiring_trials',
            ];
        }

        $overdueInvoices = $this->dashboardRepository->countOverdueInvoices();
        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'error',
                'message' => "{$overdueInvoices} fatura(s) vencida(s)",
                'action' => 'view_overdue_invoices',
            ];
        }

        $nearMessageLimit = $this->dashboardRepository->countTenantsNearMessageLimit();
        if ($nearMessageLimit > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$nearMessageLimit} empresa(s) próxima(s) do limite de mensagens",
                'action' => 'view_message_limits',
            ];
        }

        $inactiveTenants = $this->dashboardRepository->countInactiveTenants();
        if ($inactiveTenants > 5) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$inactiveTenants} empresa(s) inativa(s) há mais de 7 dias",
                'action' => 'view_inactive_tenants',
            ];
        }

        return $alerts;
    }
}
