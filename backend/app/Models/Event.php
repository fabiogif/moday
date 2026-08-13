<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'type',
        'color',
        'start_date',
        'duration_minutes',
        'location',
        'description',
        'is_active',
        'notifications_sent',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'is_active' => 'boolean',
        'notifications_sent' => 'boolean',
        'duration_minutes' => 'integer',
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
     * Relacionamento muitos-para-muitos com Client
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class, 'event_clients')
            ->withPivot(['email_sent', 'whatsapp_sent', 'sms_sent', 'notified_at'])
            ->withTimestamps();
    }

    /**
     * Scope para eventos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para eventos de um tenant específico
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para eventos futuros
     */
    public function scopeFuture($query)
    {
        return $query->where('start_date', '>=', now());
    }

    /**
     * Scope para eventos passados
     */
    public function scopePast($query)
    {
        return $query->where('start_date', '<', now());
    }

    /**
     * Scope para eventos em um período
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }

    /**
     * Calcula a data/hora de término do evento
     */
    public function getEndDateAttribute()
    {
        return $this->start_date?->addMinutes($this->duration_minutes);
    }
}

