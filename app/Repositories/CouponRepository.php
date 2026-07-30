<?php

namespace App\Repositories;

use App\Models\Coupon;
use App\Models\Order;
use App\Repositories\Contracts\CouponRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(protected Model $entity = new Coupon())
    {
    }

    public function paginateByTenant(int $tenantId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->entity->newQuery()->forTenant($tenantId), $filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function allByTenant(int $tenantId, array $filters = []): Collection
    {
        return $this->applyFilters($this->entity->newQuery()->forTenant($tenantId), $filters)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByUuid(string $uuid, int $tenantId): ?Coupon
    {
        return $this->entity->forTenant($tenantId)->where('uuid', $uuid)->first();
    }

    public function findByCode(string $code, int $tenantId): ?Coupon
    {
        return $this->entity->forTenant($tenantId)->where('code', strtoupper($code))->first();
    }

    public function createForTenant(array $data, int $tenantId): Coupon
    {
        $data['tenant_id'] = $tenantId;

        return $this->entity->create($data);
    }

    public function updateByUuid(string $uuid, int $tenantId, array $data): ?Coupon
    {
        $coupon = $this->findByUuid($uuid, $tenantId);

        if (!$coupon) {
            return null;
        }

        $coupon->update($data);

        return $coupon->fresh();
    }

    public function deleteByUuid(string $uuid, int $tenantId): bool
    {
        $coupon = $this->findByUuid($uuid, $tenantId);

        if (!$coupon) {
            return false;
        }

        return (bool) $coupon->delete();
    }

    public function incrementUsage(Coupon $coupon, int $amount = 1): void
    {
        $coupon->increment('times_redeemed', $amount);
    }

    public function countUsageForClient(int $couponId, int $tenantId, int $clientId = null, ?string $clientIdentifier = null): int
    {
        $query = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('coupon_id', $couponId);

        if ($clientId) {
            $query->where('client_id', $clientId);
        } elseif ($clientIdentifier) {
            $query->whereHas('client', function ($clientQuery) use ($clientIdentifier) {
                $clientQuery->where('email', $clientIdentifier)
                    ->orWhere('phone', preg_replace('/[^0-9]/', '', $clientIdentifier));
            });
        }

        return $query->count();
    }

    public function featured(int $tenantId, int $limit = 5): Collection
    {
        return $this->entity->forTenant($tenantId)
            ->active()
            ->where('is_featured', true)
            ->orderBy('end_at')
            ->limit($limit)
            ->get();
    }

    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['active'])) {
            $query->active();
        }

        if (!empty($filters['code'])) {
            $query->where('code', 'like', '%' . strtoupper($filters['code']) . '%');
        }

        if (!empty($filters['search'])) {
            $this->applyFullTextSearch($query, ['name', 'description'], $filters['search'], ['code']);
        }

        if (!empty($filters['type'])) {
            $query->where('discount_type', $filters['type']);
        }

        if (!empty($filters['featured'])) {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('start_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('end_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function getStatsForTenant(int $tenantId): array
    {
        $coupons = $this->allByTenant($tenantId);
        $now = Carbon::now();

        $active = $coupons->filter(fn (Coupon $coupon) => $this->isCurrentlyActive($coupon, $now))->count();
        $scheduled = $coupons->filter(fn (Coupon $coupon) => $coupon->isScheduled())->count();
        $expired = $coupons->filter(fn (Coupon $coupon) => $coupon->isExpired())->count();

        $expiringSoon = $coupons->filter(function (Coupon $coupon) use ($now) {
            if (!$coupon->end_at || $coupon->isExpired()) {
                return false;
            }

            return $coupon->end_at->diffInDays($now) <= 7;
        })->count();

        return [
            'total' => $coupons->count(),
            'active' => $active,
            'scheduled' => $scheduled,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
        ];
    }

    private function isCurrentlyActive(Coupon $coupon, Carbon $now): bool
    {
        if (!$coupon->is_active) {
            return false;
        }

        if ($coupon->start_at && $coupon->start_at->isFuture()) {
            return false;
        }

        if ($coupon->end_at && $coupon->end_at->isPast()) {
            return false;
        }

        return true;
    }
}
