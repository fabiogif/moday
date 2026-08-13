<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Tenant;
use App\Repositories\Contracts\CouponRepositoryInterface;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class CouponService
{
    public function __construct(private readonly CouponRepositoryInterface $couponRepository)
    {
    }

    /**
     * List coupons for tenant with optional pagination.
     */
    public function listCoupons(int $tenantId, array $filters = [], ?int $perPage = 15): Collection|LengthAwarePaginator
    {
        if ($perPage === null) {
            return $this->couponRepository->allByTenant($tenantId, $filters);
        }

        return $this->couponRepository->paginateByTenant($tenantId, $perPage, $filters);
    }

    /**
     * Get a single coupon by its UUID for a specific tenant.
     */
    public function getCouponByUuid(string $uuid, int $tenantId): ?Coupon
    {
        return $this->couponRepository->findByUuid($uuid, $tenantId);
    }

    /**
     * Retrieve statistics for coupons dashboard.
     */
    public function stats(int $tenantId): array
    {
        return $this->couponRepository->getStatsForTenant($tenantId);
    }

    /**
     * Create a new coupon.
     */
    public function createCoupon(array $data, int $tenantId, ?UploadedFile $imageFile = null): Coupon
    {
        $payload = $this->normalizePayload($data);

        $this->assertUniqueCode($payload['code'], $tenantId);

        return DB::transaction(function () use ($payload, $tenantId, $imageFile) {
            $coupon = $this->couponRepository->createForTenant($payload, $tenantId);

            if ($imageFile) {
                $path = $imageFile->store('coupons', 'public');
                $coupon->image = $path;
                $coupon->save();
                $coupon->refresh();
            }

            Log::info('Coupon created', [
                'coupon_id' => $coupon->id,
                'tenant_id' => $tenantId,
                'code' => $coupon->code,
            ]);

            return $coupon;
        });
    }

    /**
     * Update an existing coupon.
     */
    public function updateCoupon(string $uuid, array $data, int $tenantId, ?UploadedFile $imageFile = null, bool $removeImage = false): ?Coupon
    {
        $coupon = $this->couponRepository->findByUuid($uuid, $tenantId);
        if (!$coupon) {
            return null;
        }

        $payload = $this->normalizePayload($data, false);

        if (!empty($payload['code'])) {
            $this->assertUniqueCode($payload['code'], $tenantId, $uuid);
        }

        if ($removeImage && $coupon->image) {
            Storage::disk('public')->delete($coupon->image);
            $payload['image'] = null;
        }

        if ($imageFile) {
            if ($coupon->image) {
                Storage::disk('public')->delete($coupon->image);
            }
            $path = $imageFile->store('coupons', 'public');
            $payload['image'] = $path;
        }

        $updatedCoupon = $this->couponRepository->updateByUuid($uuid, $tenantId, $payload);

        if ($updatedCoupon) {
            Log::info('Coupon updated', [
                'coupon_id' => $updatedCoupon->id,
                'tenant_id' => $tenantId,
                'code' => $updatedCoupon->code,
            ]);
        }

        return $updatedCoupon;
    }

    /**
     * Delete a coupon.
     */
    public function deleteCoupon(string $uuid, int $tenantId): bool
    {
        $deleted = $this->couponRepository->deleteByUuid($uuid, $tenantId);

        if ($deleted) {
            Log::info('Coupon deleted', [
                'uuid' => $uuid,
                'tenant_id' => $tenantId,
            ]);
        }

        return $deleted;
    }

    /**
     * Toggle coupon status.
     */
    public function toggleCoupon(string $uuid, int $tenantId, bool $isActive): ?Coupon
    {
        $coupon = $this->couponRepository->updateByUuid($uuid, $tenantId, ['is_active' => $isActive]);

        if ($coupon) {
            Log::info('Coupon toggled', [
                'coupon_id' => $coupon->id,
                'tenant_id' => $tenantId,
                'active' => $isActive,
            ]);
        }

        return $coupon;
    }

    /**
     * Validate coupon for display purposes (without persisting usage).
     */
    public function validateForPreview(Tenant $tenant, string $code, float $orderTotal, ?int $clientId = null, ?string $clientIdentifier = null): array
    {
        $coupon = $this->fetchValidCoupon($tenant->id, $code);

        $this->assertCouponConstraints($coupon, $tenant->id, $orderTotal, $clientId, $clientIdentifier);

        $discount = $this->calculateDiscountAmount($coupon, $orderTotal);

        return [
            'coupon' => $coupon,
            'discount_amount' => $discount,
            'final_total' => max($orderTotal - $discount, 0),
        ];
    }

    /**
     * Apply coupon to order, returning discount and metadata.
     */
    public function applyCouponForOrder(Tenant $tenant, ?string $code, float $subtotal, array $context = []): array
    {
        if (!$code) {
            return [
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'final_total' => $subtotal,
                'coupon' => null,
                'coupon_snapshot' => null,
            ];
        }

        $clientId = Arr::get($context, 'client_id');
        $clientIdentifier = Arr::get($context, 'client_identifier');

        $result = $this->validateForPreview($tenant, $code, $subtotal, $clientId, $clientIdentifier);

        $coupon = $result['coupon'];
        $discountAmount = $result['discount_amount'];

        $snapshot = [
            'code' => $coupon->code,
            'name' => $coupon->name,
            'description' => $coupon->description,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'max_discount_amount' => $coupon->max_discount_amount,
            'minimum_order_amount' => $coupon->minimum_order_amount,
            'usage_limit' => $coupon->usage_limit,
            'usage_limit_per_client' => $coupon->usage_limit_per_client,
            'start_at' => optional($coupon->start_at)->toIso8601String(),
            'end_at' => optional($coupon->end_at)->toIso8601String(),
        ];

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'final_total' => max($subtotal - $discountAmount, 0),
            'coupon' => $coupon,
            'coupon_snapshot' => $snapshot,
        ];
    }

    /**
     * Persist coupon redemption after order creation.
     */
    public function registerRedemption(?Coupon $coupon, Order $order, ?int $clientId = null): void
    {
        if (!$coupon) {
            return;
        }

        $this->couponRepository->incrementUsage($coupon, 1);

        Log::info('Coupon redeemed', [
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'client_id' => $clientId,
        ]);
    }

    /**
     * Get featured coupons for storefront carousel.
     */
    public function featuredCoupons(int $tenantId, int $limit = 5): Collection
    {
        return $this->couponRepository->featured($tenantId, $limit);
    }

    /**
     * Normalize payload.
     */
    private function normalizePayload(array $data, bool $isCreate = true): array
    {
        $payload = $data;

        if (isset($payload['code'])) {
            $payload['code'] = strtoupper(trim((string) $payload['code']));
        }

        if (isset($payload['discount_type'])) {
            $payload['discount_type'] = $payload['discount_type'] === 'fixed' ? 'fixed' : 'percentage';
        }

        if (isset($payload['discount_value'])) {
            $value = (float) $payload['discount_value'];
            if (($payload['discount_type'] ?? 'percentage') === 'percentage') {
                $value = max(min($value, 100), 0);
            }
            $payload['discount_value'] = $value;
        }

        foreach (['max_discount_amount', 'minimum_order_amount'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $payload[$field] === '' ? null : (float) $payload[$field];
            }
        }

        foreach (['usage_limit', 'usage_limit_per_client'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $payload[$field] === '' ? null : (int) $payload[$field];
            }
        }

        if (array_key_exists('is_active', $payload)) {
            $payload['is_active'] = filter_var($payload['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('is_featured', $payload)) {
            $payload['is_featured'] = filter_var($payload['is_featured'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($payload['start_at']) && $payload['start_at']) {
            $payload['start_at'] = Carbon::parse($payload['start_at']);
        } elseif (array_key_exists('start_at', $payload)) {
            $payload['start_at'] = null;
        }

        if (isset($payload['end_at']) && $payload['end_at']) {
            $payload['end_at'] = Carbon::parse($payload['end_at']);
        } elseif (array_key_exists('end_at', $payload)) {
            $payload['end_at'] = null;
        }

        if ($isCreate) {
            $payload['times_redeemed'] = 0;
        }

        return $payload;
    }

    private function assertUniqueCode(string $code, int $tenantId, ?string $ignoreUuid = null): void
    {
        $existing = $this->couponRepository->findByCode($code, $tenantId);

        if ($existing && $existing->uuid !== $ignoreUuid) {
            throw new DomainException('Já existe um cupom com este código. Escolha outro código.');
        }
    }

    private function fetchValidCoupon(int $tenantId, string $code): Coupon
    {
        $coupon = $this->couponRepository->findByCode($code, $tenantId);

        if (!$coupon) {
            throw new DomainException('Cupom não encontrado. Verifique o código informado.');
        }

        return $coupon;
    }

    private function assertCouponConstraints(Coupon $coupon, int $tenantId, float $orderTotal, ?int $clientId = null, ?string $clientIdentifier = null): void
    {
        $now = Carbon::now();

        if (!$coupon->is_active) {
            throw new DomainException('Este cupom está desativado.');
        }

        if ($coupon->start_at && $coupon->start_at->isFuture()) {
            throw new DomainException('Este cupom ainda não está disponível.');
        }

        if ($coupon->end_at && $coupon->end_at->isPast()) {
            throw new DomainException('Este cupom expirou.');
        }

        if ($coupon->usage_limit !== null && $coupon->times_redeemed >= $coupon->usage_limit) {
            throw new DomainException('Limite de utilizações deste cupom foi atingido.');
        }

        if ($coupon->minimum_order_amount !== null && $orderTotal < $coupon->minimum_order_amount) {
            throw new DomainException('Valor mínimo do pedido para este cupom não atingido.');
        }

        if ($coupon->usage_limit_per_client !== null) {
            $usageCount = $this->couponRepository->countUsageForClient(
                $coupon->id,
                $tenantId,
                $clientId,
                $clientIdentifier
            );

            if ($usageCount >= $coupon->usage_limit_per_client) {
                throw new DomainException('Você já atingiu o limite de uso deste cupom.');
            }
        }
    }

    private function calculateDiscountAmount(Coupon $coupon, float $orderTotal): float
    {
        $discount = 0;

        if ($coupon->discount_type === 'percentage') {
            $discount = $orderTotal * ($coupon->discount_value / 100);

            if ($coupon->max_discount_amount !== null) {
                $discount = min($discount, $coupon->max_discount_amount);
            }
        } else {
            $discount = $coupon->discount_value;
        }

        return max(min($discount, $orderTotal), 0);
    }

    private function isCouponCurrentlyActive(Coupon $coupon, Carbon $now): bool
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
