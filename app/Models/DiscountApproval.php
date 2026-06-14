<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountApproval extends Model
{
    protected $fillable = [
        'tenant_id', 'sale_order_id', 'requested_by', 'approved_by',
        'requested_discount_pct', 'profile_max_pct', 'discount_amount',
        'status', 'notes', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_discount_pct' => 'decimal:2',
            'profile_max_pct'        => 'decimal:2',
            'discount_amount'        => 'decimal:2',
            'resolved_at'            => 'datetime',
        ];
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
