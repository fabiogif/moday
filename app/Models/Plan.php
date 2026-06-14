<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'description',
        'price',
        'periodicity',
        'is_active',
        'max_users',
        'max_products',
        'max_orders_per_month',
        'max_branches',
        'support_type',
        'integrations',
        'has_marketing',
        'has_order_completion_email',
        'has_reports',
    ];

    protected $casts = [
        'price'                      => 'decimal:2',
        'is_active'                  => 'boolean',
        'max_users'                  => 'integer',
        'max_products'               => 'integer',
        'max_orders_per_month'       => 'integer',
        'max_branches'               => 'integer',
        'integrations'               => 'array',
        'has_marketing'              => 'boolean',
        'has_order_completion_email' => 'boolean',
        'has_reports'                => 'boolean',
    ];

    public function hasFeature(string $feature): bool
    {
        // Check new plan_features table first
        $planFeature = $this->features()->where('feature_key', $feature)->first();
        if ($planFeature !== null) {
            return $planFeature->is_enabled;
        }

        // Fall back to legacy boolean columns
        return match ($feature) {
            'marketing'              => (bool) $this->has_marketing,
            'reports'                => (bool) $this->has_reports,
            'order_completion_email' => (bool) $this->has_order_completion_email,
            default                  => false,
        };
    }

    public function features()
    {
        return $this->hasMany(PlanFeature::class);
    }

    /** Monthly equivalent price for pro-rata calculations */
    public function monthlyPrice(): float
    {
        $divisor = match ($this->periodicity ?? 'monthly') {
            'quarterly'  => 3,
            'semiannual' => 6,
            'annual'     => 12,
            default      => 1,
        };

        return round((float) $this->price / $divisor, 2);
    }

    /** Check if value is unlimited (null or >= 999999) */
    public function isUnlimited(?int $value): bool
    {
        return $value === null || $value >= 999999;
    }

    public function details()
    {
        return $this->hasMany(DetailsPlan::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function profiles()
    {
        return $this->belongsToMany(Profile::class, 'plan_profile');
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    public function requiresTrial(): bool
    {
        return !$this->isFree();
    }
}
