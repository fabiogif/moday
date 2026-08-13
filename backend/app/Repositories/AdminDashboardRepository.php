<?php

namespace App\Repositories;

use App\Models\AdminActionLog;
use App\Models\Tenant;
use App\Models\TenantBilling;
use App\Models\TenantMetrics;
use App\Repositories\Contracts\AdminDashboardRepositoryInterface;

class AdminDashboardRepository implements AdminDashboardRepositoryInterface
{
    public function getTenantStats(): array
    {
        return [
            'total' => Tenant::count(),
            'active' => Tenant::where('account_status', 'active')->count(),
            'trial' => Tenant::where('account_status', 'trial')->count(),
            'expired' => Tenant::where('account_status', 'expired')->count(),
            'suspended' => Tenant::where('account_status', 'suspended')->count(),
            'blocked' => Tenant::where('is_blocked', true)->count(),
        ];
    }

    public function getFinancialStats(): array
    {
        return [
            'mrr' => Tenant::where('account_status', 'active')->sum('mrr'),
            'pending_invoices' => TenantBilling::pending()->count(),
            'overdue_invoices' => TenantBilling::overdue()->count(),
            'total_revenue_month' => TenantBilling::paid()
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),
        ];
    }

    public function getUsageStats(): array
    {
        return [
            'logins_today' => TenantMetrics::today()->sum('login_count'),
            'orders_today' => TenantMetrics::today()->sum('total_orders'),
            'revenue_today' => TenantMetrics::today()->sum('total_revenue'),
            'messages_today' => TenantMetrics::today()->sum('messages_sent'),
        ];
    }

    public function getGrowthStats(): array
    {
        return [
            'new_tenants_month' => Tenant::whereMonth('created_at', now()->month)->count(),
            'new_tenants_week' => Tenant::where('created_at', '>=', now()->subWeek())->count(),
            'churn_rate' => $this->calculateChurnRate(),
        ];
    }

    public function calculateChurnRate(): float
    {
        $startOfMonth = now()->startOfMonth();
        $activeStart = Tenant::where('account_status', 'active')
            ->where('activated_at', '<', $startOfMonth)
            ->count();

        if ($activeStart === 0) {
            return 0;
        }

        $churned = Tenant::where('account_status', 'expired')
            ->whereBetween('updated_at', [$startOfMonth, now()])
            ->count();

        return round(($churned / $activeStart) * 100, 2);
    }

    public function getRecentActivities(int $limit = 20): mixed
    {
        return AdminActionLog::with(['adminUser:id,name,email', 'tenant:id,name'])
            ->recent()
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'admin' => $log->adminUser ? [
                    'id' => $log->adminUser->id,
                    'name' => $log->adminUser->name,
                    'email' => $log->adminUser->email,
                ] : null,
                'tenant' => $log->tenant ? [
                    'id' => $log->tenant->id,
                    'name' => $log->tenant->name,
                ] : null,
                'created_at' => $log->created_at->toIso8601String(),
            ]);
    }

    public function countTrialsExpiringSoon(): int
    {
        return Tenant::where('account_status', 'trial')
            ->where('trial_expires_at', '<=', now()->addDays(3))
            ->where('trial_expires_at', '>', now())
            ->count();
    }

    public function countOverdueInvoices(): int
    {
        return TenantBilling::overdue()->count();
    }

    public function countTenantsNearMessageLimit(): int
    {
        return Tenant::whereRaw('messages_limit > 0')
            ->whereRaw('messages_sent >= messages_limit * 0.9')
            ->count();
    }

    public function countInactiveTenants(): int
    {
        return Tenant::where('last_login_at', '<', now()->subDays(7))
            ->orWhereNull('last_login_at')
            ->count();
    }
}
