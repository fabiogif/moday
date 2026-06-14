<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'uuid', 'identify', 'supplier_id', 'approved_by',
        'status', 'expected_delivery', 'received_at',
        'subtotal', 'discount_amount', 'tax_amount', 'freight_amount', 'total',
        'payment_term_days', 'payment_method', 'notes', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_delivery' => 'date',
            'received_at' => 'datetime',
            'archived_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'freight_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    const STATUSES = [
        'rascunho'  => 'Rascunho',
        'enviado'   => 'Enviado',
        'confirmado'=> 'Confirmado',
        'recebido'  => 'Recebido',
        'cancelado' => 'Cancelado',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->uuid ??= Str::uuid();
            if (empty($m->identify)) {
                $m->identify = 'PC-' . strtoupper(Str::random(6));
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['rascunho', 'enviado']);
    }

    public function canReceive(): bool
    {
        return $this->status === 'confirmado';
    }
}
