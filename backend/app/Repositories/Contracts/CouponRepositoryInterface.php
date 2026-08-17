<?php

namespace App\Repositories\Contracts;

use App\Models\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CouponRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateByTenant(int $tenantId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function allByTenant(int $tenantId, array $filters = []): Collection;

    public function findByUuid(string $uuid, int $tenantId): ?Coupon;

    public function findByCode(string $code, int $tenantId): ?Coupon;

    public function createForTenant(array $data, int $tenantId): Coupon;

    public function updateByUuid(string $uuid, int $tenantId, array $data): ?Coupon;

    public function deleteByUuid(string $uuid, int $tenantId): bool;

    public function incrementUsage(Coupon $coupon, int $amount = 1): void;

    public function countUsageForClient(int $couponId, int $tenantId, int $clientId = null, ?string $clientIdentifier = null): int;

    public function featured(int $tenantId, int $limit = 5): Collection;

    public function getStatsForTenant(int $tenantId): array;
}
