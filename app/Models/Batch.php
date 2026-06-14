<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'product_id', 'warehouse_id', 'supplier_id', 'purchase_order_id',
        'batch_number', 'manufacture_date', 'expiry_date',
        'quantity_received', 'quantity_available', 'quantity_reserved', 'quantity_sold',
        'unit_cost', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'manufacture_date' => 'date',
            'expiry_date' => 'date',
            'quantity_received' => 'decimal:3',
            'quantity_available' => 'decimal:3',
            'quantity_reserved' => 'decimal:3',
            'quantity_sold' => 'decimal:3',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // FEFO: oldest expiry_date first
    public function scopeFefo($query)
    {
        return $query->where('status', 'available')
            ->where('quantity_available', '>', 0)
            ->orderBy('expiry_date', 'asc');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('quantity_available', '>', 0);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', Carbon::now()->addDays($days))
            ->where('expiry_date', '>=', Carbon::now())
            ->where('quantity_available', '>', 0);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', Carbon::now());
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function daysUntilExpiry(): ?int
    {
        return $this->expiry_date ? (int) Carbon::now()->diffInDays($this->expiry_date, false) : null;
    }
}
