<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StoreHour extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'is_always_open',
        'day_of_week',
        'delivery_type',
        'start_time',
        'end_time',
        'start_time_2',
        'end_time_2',
        'is_active',
    ];

    protected $casts = [
        'is_always_open' => 'boolean',
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_time_2' => 'datetime:H:i',
        'end_time_2' => 'datetime:H:i',
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

    /**
     * Relacionamento com Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para horários de um tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para horários ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para horários por dia da semana
     */
    public function scopeByDayOfWeek($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope para horários por tipo de entrega
     */
    public function scopeByDeliveryType($query, string $deliveryType)
    {
        return $query->whereIn('delivery_type', [$deliveryType, 'both']);
    }

    /**
     * Retorna o nome do dia da semana
     */
    public function getDayNameAttribute(): string
    {
        return match($this->day_of_week) {
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            default => 'Indefinido',
        };
    }

    /**
     * Retorna o nome abreviado do dia da semana
     */
    public function getDayNameShortAttribute(): string
    {
        return match($this->day_of_week) {
            0 => 'Dom',
            1 => 'Seg',
            2 => 'Ter',
            3 => 'Qua',
            4 => 'Qui',
            5 => 'Sex',
            6 => 'Sáb',
            default => '-',
        };
    }

    /**
     * Retorna o label do tipo de entrega
     */
    public function getDeliveryTypeLabelAttribute(): string
    {
        return match($this->delivery_type) {
            'delivery' => 'Apenas Entrega',
            'pickup' => 'Apenas Retirada',
            'both' => 'Entrega e Retirada',
            default => $this->delivery_type,
        };
    }

    /**
     * Verifica se há sobreposição de horários
     */
    public static function hasOverlap(int $tenantId, int $dayOfWeek, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $query = self::where('tenant_id', $tenantId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where(function ($q) use ($startTime, $endTime) {
                // Verifica se o novo horário sobrepõe algum existente
                $q->where(function ($subQ) use ($startTime, $endTime) {
                    // Novo horário começa durante um horário existente
                    $subQ->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })->orWhere(function ($subQ) use ($startTime, $endTime) {
                    // Novo horário termina durante um horário existente
                    $subQ->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })->orWhere(function ($subQ) use ($startTime, $endTime) {
                    // Novo horário engloba completamente um horário existente
                    $subQ->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}

