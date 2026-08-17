<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'loyalty_program_id',
        'client_id',
        'type',
        'points',
        'balance_after',
        'order_id',
        'loyalty_reward_id',
        'purchase_amount',
        'multiplier',
        'description',
        'expires_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'purchase_amount' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'expires_at' => 'date',
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
        });
    }

    public function loyaltyProgram()
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function loyaltyReward()
    {
        return $this->belongsTo(LoyaltyReward::class);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->format('Y-m-d'))
            ->where('type', 'earn');
    }

    public function scopeActive($query)
    {
        return $query->where('type', 'earn')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now()->format('Y-m-d'));
            });
    }

    /**
     * Verifica se está expirado
     */
    public function isExpired(): bool
    {
        if ($this->type !== 'earn') {
            return false;
        }

        return $this->expires_at && $this->expires_at->lt(now());
    }

    /**
     * Retorna label do tipo
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'earn' => 'Ganho',
            'redeem' => 'Resgate',
            'expire' => 'Expirado',
            'adjust' => 'Ajuste',
            default => $this->type,
        };
    }
}

