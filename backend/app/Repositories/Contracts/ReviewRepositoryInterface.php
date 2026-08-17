<?php

namespace App\Repositories\Contracts;

use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;

interface ReviewRepositoryInterface
{
    public function getApprovedForTenant(int $tenantId, int $limit = 10): Collection;

    public function getFeaturedForTenant(int $tenantId, int $limit = 5): Collection;

    public function getPendingForTenant(int $tenantId): Collection;

    public function getAllForTenant(int $tenantId, ?string $status = null): Collection;

    public function getByOrderId(int $orderId): ?Review;

    public function getStats(int $tenantId): array;

    public function getRecentForDashboard(int $tenantId, int $limit = 5): Collection;

    public function findByUuid(string $uuid): ?Review;

    public function deleteByUuid(string $uuid): bool;

    public function approve(string $uuid, int $moderatorId, ?string $reason = null): ?Review;

    public function reject(string $uuid, int $moderatorId, ?string $reason = null): ?Review;

    public function toggleFeatured(string $uuid): ?Review;
}
