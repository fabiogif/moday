<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoyaltyRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'loyalty_reward_id',
        'client_id',
        'loyalty_transaction_id',
        'points_used',
        'status',
        'order_id',
        'coupon_code',
        'redeemed_at',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'points_used' => 'integer',
        'redeemed_at' => 'date',
        'expires_at' => 'date',
        'used_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }

            if (empty($model->coupon_code)) {
                $model->coupon_code = static::generateCouponCode();
            }

            if (empty($model->redeemed_at)) {
                $model->redeemed_at = now();
            }
        });
    }

    public function loyaltyReward()
    {
        return $this->belongsTo(LoyaltyReward::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function loyaltyTransaction()
    {
        return $this->belongsTo(LoyaltyTransaction::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<', now()->format('Y-m-d'));
            });
    }

    /**
     * Verifica se está expirado
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->status !== 'pending') {
            return false;
        }

        return $this->expires_at && $this->expires_at->lt(now());
    }

    /**
     * Marca como usado
     */
    public function markAsUsed(int $orderId): void
    {
        $this->update([
            'status' => 'used',
            'order_id' => $orderId,
            'used_at' => now(),
        ]);
    }

    /**
     * Marca como expirado
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Marca como cancelado
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Gera código de cupom único
     */
    public static function generateCouponCode(): string
    {
        do {
            $code = 'LOYALTY-' . strtoupper(Str::random(8));
        } while (static::where('coupon_code', $code)->exists());

        return $code;
    }

    /**
     * Retorna label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'used' => 'Usado',
            'expired' => 'Expirado',
            'cancelled' => 'Cancelado',
            default => $this->status,
        };
    }
}

