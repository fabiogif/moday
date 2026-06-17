<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SaleOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'uuid', 'identify', 'client_id', 'parent_sale_order_id', 'approved_by',
        'status', 'type', 'prescription_verified',
        'subtotal', 'discount_amount', 'tax_amount', 'freight_amount', 'total',
        'payment_term_days', 'payment_method', 'installments', 'due_date', 'split_payments',
        'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zipcode', 'use_client_address',
        'shipping_latitude', 'shipping_longitude',
        'estimated_delivery', 'delivery_window_start', 'delivery_window_end',
        'nfe_number', 'nfe_series', 'nfe_key', 'nfe_status', 'nfe_issued_at',
        'ordered_at', 'scheduled_at', 'is_scheduled', 'approved_at', 'billed_at', 'delivered_at',
        'notes', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'split_payments' => 'array',
            'shipping_address' => 'array',
            'due_date' => 'date',
            'estimated_delivery' => 'date',
            'nfe_issued_at' => 'datetime',
            'ordered_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'is_scheduled' => 'boolean',
            'use_client_address' => 'boolean',
            'approved_at' => 'datetime',
            'billed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'archived_at' => 'datetime',
            'prescription_verified' => 'boolean',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'freight_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    const STATUSES = [
        'orcamento' => 'Orçamento',
        'aprovado'  => 'Aprovado',
        'separacao' => 'Em Separação',
        'faturado'  => 'Faturado',
        'entregue'  => 'Entregue',
        'cancelado' => 'Cancelado',
    ];

    const STATUS_FLOW = [
        'orcamento' => 'aprovado',
        'aprovado'  => 'separacao',
        'separacao' => 'faturado',
        'faturado'  => 'entregue',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->uuid ??= Str::uuid();
            if (empty($m->identify)) {
                $m->identify = 'PV-' . strtoupper(Str::random(6));
            }
            $m->ordered_at ??= now();
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(SaleOrderItem::class);
    }

    public function shipments()
    {
        return $this->belongsToMany(Shipment::class, 'shipment_sale_order')
            ->withPivot([
                'delivery_sequence',
                'delivery_window_start',
                'delivery_window_end',
                'delivery_zipcode',
                'pod_photo_path',
                'pod_signature_path',
                'pod_delivered_at',
                'pod_recipient_name',
                'pod_latitude',
                'pod_longitude',
                'pod_status',
                'pod_notes',
            ])
            ->orderByDesc('shipment_sale_order.id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus($query, string $status)
    {
        $statuses = array_map('trim', explode(',', $status));
        return count($statuses) > 1
            ? $query->whereIn('status', $statuses)
            : $query->where('status', $status);
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function nextStatus(): ?string
    {
        return self::STATUS_FLOW[$this->status] ?? null;
    }

    public function canAdvance(): bool
    {
        return isset(self::STATUS_FLOW[$this->status]);
    }

    public function advanceStatus(int $userId): bool
    {
        $next = $this->nextStatus();
        if (!$next) return false;

        $timestamps = [
            'aprovado'  => ['approved_at' => now(), 'approved_by' => $userId],
            'faturado'  => ['billed_at' => now()],
            'entregue'  => ['delivered_at' => now()],
        ];

        $this->update(array_merge(['status' => $next], $timestamps[$next] ?? []));
        return true;
    }
}
