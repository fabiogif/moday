<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\TenantBilling;
use App\Models\TenantMetrics;
use App\Repositories\Contracts\AdminMetricsRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AdminMetricsRepository implements AdminMetricsRepositoryInterface
{
    private function monthExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    public function getTotalMrr(): float
    {
        return (float) Tenant::where('account_status', 'active')->sum('mrr');
    }

    public function getRevenueByMonth($startDate, $endDate): mixed
    {
        $monthFormat = $this->monthExpression('paid_at');

        return TenantBilling::paid()
            ->forPeriod($startDate, $endDate)
            ->select(
                DB::raw("{$monthFormat} as month"),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function getRevenueByPlan(): mixed
    {
        return Tenant::where('account_status', 'active')
            ->select('subscription_plan', DB::raw('COUNT(*) as count'), DB::raw('SUM(mrr) as total_mrr'))
            ->groupBy('subscription_plan')
            ->get();
    }

    public function getTopTenantsByMrr(int $limit = 10): mixed
    {
        return Tenant::where('account_status', 'active')
            ->orderBy('mrr', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'subscription_plan', 'mrr']);
    }

    public function getLoginsByDay($startDate, $endDate): mixed
    {
        return TenantMetrics::forPeriod($startDate, $endDate)
            ->select('metric_date', DB::raw('SUM(login_count) as total_logins'))
            ->groupBy('metric_date')
            ->orderBy('metric_date')
            ->get();
    }

    public function getActiveTenantsPerDay($startDate, $endDate): mixed
    {
        return TenantMetrics::forPeriod($startDate, $endDate)
            ->select('metric_date', DB::raw('COUNT(DISTINCT tenant_id) as active_count'))
            ->where('login_count', '>', 0)
            ->groupBy('metric_date')
            ->orderBy('metric_date')
            ->get();
    }

    public function getMostActiveTenants($startDate, $endDate, int $limit = 10): mixed
    {
        return TenantMetrics::forPeriod($startDate, $endDate)
            ->select('tenant_id', DB::raw('SUM(login_count) as total_logins'))
            ->groupBy('tenant_id')
            ->orderBy('total_logins', 'desc')
            ->limit($limit)
            ->with('tenant:id,name')
            ->get()
            ->map(fn ($metric) => [
                'tenant_id' => $metric->tenant_id,
                'tenant_name' => $metric->tenant->name ?? 'N/A',
                'total_logins' => $metric->total_logins,
            ]);
    }

    public function countInactiveTenants(): int
    {
        return Tenant::where('last_login_at', '<', now()->subDays(7))
            ->orWhereNull('last_login_at')
            ->count();
    }

    public function getAdoptionRate(): float
    {
        $totalActive = Tenant::whereIn('account_status', ['active', 'trial'])->count();
        $recentlyUsed = Tenant::where('last_login_at', '>=', now()->subDays(7))->count();

        return $totalActive > 0 ? round(($recentlyUsed / $totalActive) * 100, 2) : 0;
    }

    public function getMessagesByDay($startDate, $endDate): mixed
    {
        return TenantMetrics::forPeriod($startDate, $endDate)
            ->select(
                'metric_date',
                DB::raw('SUM(messages_sent) as total'),
                DB::raw('SUM(messages_whatsapp) as whatsapp'),
                DB::raw('SUM(messages_email) as email'),
                DB::raw('SUM(messages_sms) as sms')
            )
            ->groupBy('metric_date')
            ->orderBy('metric_date')
            ->get();
    }

    public function getMessageTotals($startDate, $endDate): mixed
    {
        return TenantMetrics::forPeriod($startDate, $endDate)
            ->select(
                DB::raw('SUM(messages_sent) as total'),
                DB::raw('SUM(messages_whatsapp) as whatsapp'),
                DB::raw('SUM(messages_email) as email'),
                DB::raw('SUM(messages_sms) as sms')
            )
            ->first();
    }

    public function getTopMessageSenders($startDate, $endDate, int $limit = 10): mixed
    {
        return TenantMetrics::forPeriod($startDate, $endDate)
            ->select('tenant_id', DB::raw('SUM(messages_sent) as total_messages'))
            ->groupBy('tenant_id')
            ->orderBy('total_messages', 'desc')
            ->limit($limit)
            ->with('tenant:id,name,messages_limit')
            ->get()
            ->map(fn ($metric) => [
                'tenant_id' => $metric->tenant_id,
                'tenant_name' => $metric->tenant->name ?? 'N/A',
                'total_messages' => $metric->total_messages,
                'messages_limit' => $metric->tenant->messages_limit ?? 0,
            ]);
    }

    public function getTenantsNearMessageLimit(): mixed
    {
        return Tenant::whereRaw('messages_limit > 0')
            ->whereRaw('messages_sent >= messages_limit * 0.9')
            ->get(['id', 'name', 'messages_sent', 'messages_limit'])
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'sent' => $tenant->messages_sent ?? 0,
                'limit' => $tenant->messages_limit,
                'percentage' => round((($tenant->messages_sent ?? 0) / $tenant->messages_limit) * 100, 1),
            ]);
    }

    public function getNewTenantsByMonth(int $months = 12): mixed
    {
        $monthFormat = $this->monthExpression('created_at');

        return Tenant::where('created_at', '>=', now()->subMonths($months))
            ->select(DB::raw("{$monthFormat} as month"), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function getConversionsByMonth(int $months = 12): mixed
    {
        $monthFormat = $this->monthExpression('activated_at');

        return Tenant::where('activated_at', '>=', now()->subMonths($months))
            ->whereNotNull('activated_at')
            ->select(DB::raw("{$monthFormat} as month"), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function getChurnByMonth(int $months = 12): mixed
    {
        $monthFormat = $this->monthExpression('updated_at');

        return Tenant::where('account_status', 'expired')
            ->where('updated_at', '>=', now()->subMonths($months))
            ->select(DB::raw("{$monthFormat} as month"), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function getGrowthStats(): array
    {
        $totalTrials = Tenant::where('account_status', 'trial')->count();
        $totalActive = Tenant::where('account_status', 'active')->count();
        $allTimeTrials = Tenant::whereNotNull('trial_started_at')->count();
        $conversionRate = $allTimeTrials > 0
            ? round(($totalActive / $allTimeTrials) * 100, 2)
            : 0;

        return [
            'total_trials' => $totalTrials,
            'total_active' => $totalActive,
            'total_expired' => Tenant::where('account_status', 'expired')->count(),
            'conversion_rate' => $conversionRate,
        ];
    }

    public function getTenantDailyMetrics(int $tenantId, $startDate, $endDate): mixed
    {
        return TenantMetrics::forTenant($tenantId)
            ->forPeriod($startDate, $endDate)
            ->orderBy('metric_date')
            ->get();
    }

    public function getTenantAggregatedMetrics(int $tenantId, $startDate, $endDate): mixed
    {
        return TenantMetrics::getAggregated($tenantId, $startDate, $endDate);
    }
}
