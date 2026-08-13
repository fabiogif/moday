<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Tenant;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'code',
        'name',
        'description',
        'image',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'minimum_order_amount',
        'usage_limit',
        'usage_limit_per_client',
        'times_redeemed',
        'start_at',
        'end_at',
        'is_active',
        'is_featured',
        'metadata',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $coupon) {
            if (empty($coupon->uuid)) {
                $coupon->uuid = Str::uuid();
            }

            $coupon->code = strtoupper($coupon->code);
        });

        static::updating(function (self $coupon) {
            if (!empty($coupon->code)) {
                $coupon->code = strtoupper($coupon->code);
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($query) {
                $now = Carbon::now();
                $query->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($query) {
                $now = Carbon::now();
                $query->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function isExpired(): bool
    {
        return $this->end_at instanceof Carbon && $this->end_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->start_at instanceof Carbon && $this->start_at->isFuture();
    }

    public function remainingUses(): ?int
    {
        if ($this->usage_limit === null) {
            return null;
        }

        return max($this->usage_limit - $this->times_redeemed, 0);
    }
}
