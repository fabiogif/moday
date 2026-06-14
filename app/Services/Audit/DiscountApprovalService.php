<?php

namespace App\Services\Audit;

use App\Models\DiscountApproval;
use App\Models\Profile;
use App\Models\SaleOrder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DiscountApprovalService
{
    /**
     * Checks if the discount on a sale order exceeds the user's profile limit.
     * Returns null when no approval is needed.
     */
    public function evaluateDiscount(SaleOrder $order, User $user): ?DiscountApproval
    {
        $subtotal = (float) ($order->subtotal ?? 0);
        if ($subtotal <= 0 || (float) ($order->discount_amount ?? 0) <= 0) {
            return null;
        }

        $discountPct = round(((float) $order->discount_amount / $subtotal) * 100, 2);

        $profile = Profile::find($user->profile_id ?? null);
        $maxPct  = $profile ? (float) ($profile->max_discount_percent ?? 100) : 100;

        if ($maxPct === null || $discountPct <= $maxPct) {
            return null; // within limit, no approval needed
        }

        return $this->createRequest($order, $user, $discountPct, $maxPct);
    }

    public function createRequest(
        SaleOrder $order,
        User $requestedBy,
        float $discountPct,
        float $profileMaxPct,
    ): DiscountApproval {
        return DB::transaction(function () use ($order, $requestedBy, $discountPct, $profileMaxPct) {
            // Cancel any previous pending request for the same order
            DiscountApproval::forTenant($order->tenant_id)
                ->where('sale_order_id', $order->id)
                ->pending()
                ->update(['status' => 'cancelled', 'resolved_at' => now()]);

            return DiscountApproval::create([
                'tenant_id'              => $order->tenant_id,
                'sale_order_id'          => $order->id,
                'requested_by'           => $requestedBy->id,
                'requested_discount_pct' => $discountPct,
                'profile_max_pct'        => $profileMaxPct,
                'discount_amount'        => $order->discount_amount,
                'status'                 => 'pending',
            ]);
        });
    }

    public function approve(int $tenantId, int $approvalId, User $approver, ?string $notes = null): DiscountApproval
    {
        $approval = DiscountApproval::forTenant($tenantId)->findOrFail($approvalId);

        if (!$approval->isPending()) {
            throw new \DomainException('Solicitação já resolvida.');
        }

        $approval->update([
            'status'      => 'approved',
            'approved_by' => $approver->id,
            'notes'       => $notes,
            'resolved_at' => now(),
        ]);

        return $approval->fresh();
    }

    public function reject(int $tenantId, int $approvalId, User $approver, ?string $notes = null): DiscountApproval
    {
        $approval = DiscountApproval::forTenant($tenantId)->findOrFail($approvalId);

        if (!$approval->isPending()) {
            throw new \DomainException('Solicitação já resolvida.');
        }

        $approval->update([
            'status'      => 'rejected',
            'approved_by' => $approver->id,
            'notes'       => $notes,
            'resolved_at' => now(),
        ]);

        return $approval->fresh();
    }

    public function listForTenant(int $tenantId, array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        $query = DiscountApproval::forTenant($tenantId)
            ->with([
                'saleOrder:id,identify,total,discount_amount',
                'requestedBy:id,name',
                'approvedBy:id,name',
            ])
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['sale_order_id'])) {
            $query->where('sale_order_id', (int) $filters['sale_order_id']);
        }

        return $query->paginate($perPage);
    }

    public function pendingCount(int $tenantId): int
    {
        return DiscountApproval::forTenant($tenantId)->pending()->count();
    }
}
