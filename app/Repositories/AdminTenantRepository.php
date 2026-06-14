<?php

namespace App\Repositories;

use App\Models\AdminActionLog;
use App\Models\Tenant;
use App\Models\TenantAccessLog;
use App\Models\TenantBilling;
use App\Models\TenantMetrics;
use App\Repositories\Contracts\AdminTenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminTenantRepository implements AdminTenantRepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Tenant::query();

        if (!empty($filters['status'])) {
            $query->where('account_status', $filters['status']);
        }

        if (!empty($filters['plan'])) {
            $query->where('subscription_plan', $filters['plan']);
        }

        if (isset($filters['blocked'])) {
            $query->where('is_blocked', filter_var($filters['blocked'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subdomain', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    public function findOrFail(int $id): Tenant
    {
        return Tenant::findOrFail($id);
    }

    public function findWithTrashedOrFail(int $id): Tenant
    {
        return Tenant::withTrashed()->findOrFail($id);
    }

    public function getMetricsForPeriod(int $tenantId, $startDate, $endDate): mixed
    {
        return TenantMetrics::forTenant($tenantId)
            ->forPeriod($startDate, $endDate)
            ->get();
    }

    public function getBillingHistory(int $tenantId, int $limit = 12): mixed
    {
        return TenantBilling::forTenant($tenantId)
            ->orderBy('billing_date', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecentAccess(int $tenantId, int $limit = 10): mixed
    {
        return TenantAccessLog::forTenant($tenantId)
            ->with('user:id,name,email')
            ->recent()
            ->limit($limit)
            ->get();
    }

    public function getActionHistory(int $tenantId, int $limit = 20): mixed
    {
        return AdminActionLog::forTenant($tenantId)
            ->with('adminUser:id,name,email')
            ->recent()
            ->limit($limit)
            ->get();
    }

    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->fresh();
    }

    public function softDelete(Tenant $tenant): bool
    {
        return (bool) $tenant->delete();
    }

    public function forceDelete(Tenant $tenant): bool
    {
        return (bool) $tenant->forceDelete();
    }

    public function getTenantsByIds(array $ids): mixed
    {
        return Tenant::whereIn('id', $ids)->get();
    }
}
