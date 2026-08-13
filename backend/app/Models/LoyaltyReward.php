<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LoyaltyReward extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'loyalty_program_id',
        'name',
        'description',
        'type',
        'points_required',
        'discount_value',
        'product_id',
        'stock_quantity',
        'max_redemptions_per_user',
        'validity_days',
        'is_active',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'discount_value' => 'decimal:2',
        'stock_quantity' => 'integer',
        'max_redemptions_per_user' => 'integer',
        'validity_days' => 'integer',
        'is_active' => 'boolean',
        'available_from' => 'date',
        'available_until' => 'date',
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

    public function loyaltyProgram()
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function redemptions()
    {
        return $this->hasMany(LoyaltyRedemption::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        $today = now()->format('Y-m-d');
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', $today);
            });
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('stock_quantity')
                ->orWhere('stock_quantity', '>', 0);
        });
    }

    /**
     * Verifica se está disponível
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $today = now();

        if ($this->available_from && $today->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $today->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se tem estoque
     */
    public function hasStock(): bool
    {
        return $this->stock_quantity === null || $this->stock_quantity > 0;
    }

    /**
     * Decrementa estoque
     */
    public function decrementStock(): void
    {
        if ($this->stock_quantity !== null) {
            $this->decrement('stock_quantity');
        }
    }

    /**
     * Verifica se cliente pode resgatar
     */
    public function canBeRedeemedBy(int $clientId): bool
    {
        if (!$this->max_redemptions_per_user) {
            return true;
        }

        $count = $this->redemptions()
            ->where('client_id', $clientId)
            ->whereIn('status', ['pending', 'used'])
            ->count();

        return $count < $this->max_redemptions_per_user;
    }

    /**
     * Retorna label do tipo
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'discount_percentage' => 'Desconto Percentual',
            'discount_fixed' => 'Desconto Fixo',
            'free_product' => 'Produto Grátis',
            'free_shipping' => 'Frete Grátis',
            'custom' => 'Personalizado',
            default => $this->type,
        };
    }
}

