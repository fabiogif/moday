<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'product_id', 'batch_id', 'warehouse_id', 'performed_by',
        'type', 'quantity', 'unit_cost',
        'reference_type', 'reference_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
        ];
    }

    const TYPES = [
        'entrada'      => 'Entrada',
        'saida'        => 'Saída',
        'transferencia'=> 'Transferência',
        'ajuste'       => 'Ajuste',
        'devolucao'    => 'Devolução',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeEntradas($query)
    {
        return $query->where('type', 'entrada');
    }

    public function scopeSaidas($query)
    {
        return $query->where('type', 'saida');
    }
}
