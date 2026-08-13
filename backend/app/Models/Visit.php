<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Visita comercial agendada por um vendedor a um cliente. Regra de negócio
 * (transições de status, detecção de conflito de horário) vive em
 * App\Services\Visit\VisitService / VisitStatusMachine, não neste Model.
 */
class Visit extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = ['venda', 'pos_venda', 'cobranca', 'prospeccao', 'entrega', 'treinamento', 'suporte', 'checkin'];
    public const PRIORITIES = ['baixa', 'normal', 'alta', 'urgente'];
    public const STATUSES = ['agendada', 'em_andamento', 'concluida', 'cancelada', 'reagendada', 'cliente_ausente', 'sem_sucesso'];
    public const TERMINAL_STATUSES = ['concluida', 'cancelada', 'sem_sucesso'];
    public const RESULTS = ['venda_realizada', 'sem_interesse', 'apenas_visita', 'pendencia', 'sem_sucesso'];

    protected $fillable = [
        'tenant_id', 'uuid', 'user_id', 'client_id', 'created_by_user_id',
        'scheduled_date', 'scheduled_start_time', 'scheduled_end_time',
        'type', 'priority', 'status', 'objective_notes',
        'recurrence_id', 'rescheduled_from_visit_id',
        'checkin_at', 'checkin_lat', 'checkin_lng', 'checkin_address', 'checkin_distance_meters', 'checkin_out_of_range',
        'checkout_at', 'checkout_lat', 'checkout_lng', 'checkout_address', 'service_duration_minutes',
        'result', 'order_value', 'sale_order_id',
        'has_pending_issue', 'next_visit_suggested_at',
        'client_request_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'checkin_at' => 'datetime',
            'checkin_lat' => 'decimal:7',
            'checkin_lng' => 'decimal:7',
            'checkin_out_of_range' => 'boolean',
            'checkout_at' => 'datetime',
            'checkout_lat' => 'decimal:7',
            'checkout_lng' => 'decimal:7',
            'order_value' => 'decimal:2',
            'has_pending_issue' => 'boolean',
            'next_visit_suggested_at' => 'date',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function (self $visit) {
            $visit->uuid ??= (string) Str::uuid();
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function recurrence()
    {
        return $this->belongsTo(VisitRecurrence::class, 'recurrence_id');
    }

    public function rescheduledFrom()
    {
        return $this->belongsTo(self::class, 'rescheduled_from_visit_id');
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(VisitStatusHistory::class)->orderByDesc('occurred_at');
    }

    public function media()
    {
        return $this->hasMany(VisitMedia::class);
    }

    public function presentedProducts()
    {
        return $this->hasMany(VisitPresentedProduct::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(VisitAuditLog::class)->orderByDesc('created_at');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }
}
