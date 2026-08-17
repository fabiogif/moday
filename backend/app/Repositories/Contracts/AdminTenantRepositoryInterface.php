<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminTenantRepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(int $id): Tenant;

    public function findWithTrashedOrFail(int $id): Tenant;

    public function getMetricsForPeriod(int $tenantId, $startDate, $endDate): mixed;

    public function getBillingHistory(int $tenantId, int $limit = 12): mixed;

    public function getRecentAccess(int $tenantId, int $limit = 10): mixed;

    public function getActionHistory(int $tenantId, int $limit = 20): mixed;

    public function updateTenant(Tenant $tenant, array $data): Tenant;

    public function softDelete(Tenant $tenant): bool;

    public function forceDelete(Tenant $tenant): bool;

    public function getTenantsByIds(array $ids): mixed;

    public function findOwnerUser(int $tenantId): ?User;

    public function emailExistsForTenant(int $tenantId, string $email, int $excludeUserId): bool;

    public function updateOwnerUser(User $user, array $data): User;
}
