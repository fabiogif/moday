<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shipment extends Model
{
    protected $fillable = [
        'tenant_id', 'uuid', 'identify', 'carrier_id', 'route_name', 'status',
        'driver_name', 'vehicle_plate', 'shipped_at', 'delivered_at',
        'pod_reference', 'notes', 'created_by',
        'region', 'optimized_route', 'estimated_km', 'delivery_cost', 'cost_per_delivery',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at'        => 'datetime',
            'delivered_at'      => 'datetime',
            'optimized_route'   => 'array',
            'estimated_km'      => 'decimal:2',
            'delivery_cost'     => 'decimal:2',
            'cost_per_delivery' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->uuid ??= Str::uuid();
            $m->identify ??= 'ROM-' . strtoupper(Str::random(6));
        });
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }

    public function saleOrders()
    {
        return $this->belongsToMany(SaleOrder::class, 'shipment_sale_order');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
