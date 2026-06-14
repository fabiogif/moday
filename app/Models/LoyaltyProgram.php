<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LoyaltyProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'description',
        'is_active',
        'points_per_currency',
        'min_purchase_amount',
        'max_points_per_purchase',
        'points_expiry_days',
        'excluded_categories',
        'excluded_products',
        'birthday_multiplier',
        'special_day_multipliers',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'points_per_currency' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_points_per_purchase' => 'decimal:2',
        'points_expiry_days' => 'integer',
        'excluded_categories' => 'array',
        'excluded_products' => 'array',
        'birthday_multiplier' => 'decimal:2',
        'special_day_multipliers' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rewards()
    {
        return $this->hasMany(LoyaltyReward::class);
    }

    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Calcula pontos para um valor de compra
     */
    public function calculatePoints(float $amount, float $multiplier = 1.0): int
    {
        if ($this->min_purchase_amount && $amount < $this->min_purchase_amount) {
            return 0;
        }

        $points = floor($amount * $this->points_per_currency * $multiplier);

        if ($this->max_points_per_purchase) {
            $points = min($points, $this->max_points_per_purchase);
        }

        return (int) $points;
    }

    /**
     * Verifica se uma categoria está excluída
     */
    public function isCategoryExcluded(int $categoryId): bool
    {
        if (!$this->excluded_categories) {
            return false;
        }

        return in_array($categoryId, $this->excluded_categories);
    }

    /**
     * Verifica se um produto está excluído
     */
    public function isProductExcluded(int $productId): bool
    {
        if (!$this->excluded_products) {
            return false;
        }

        return in_array($productId, $this->excluded_products);
    }

    /**
     * Obtém multiplicador para uma data específica
     */
    public function getMultiplierForDate(\DateTime $date): float
    {
        $dayOfWeek = $date->format('w'); // 0 (domingo) a 6 (sábado)

        if ($this->special_day_multipliers && isset($this->special_day_multipliers[$dayOfWeek])) {
            return (float) $this->special_day_multipliers[$dayOfWeek];
        }

        return 1.0;
    }
}

