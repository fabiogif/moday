<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shipment extends Model
{
    protected $fillable = [
        'tenant_id', 'uuid', 'identify', 'carrier_id', 'vehicle_id', 'driver_id',
        'route_name', 'status',
        'driver_name', 'vehicle_plate', 'shipped_at', 'delivered_at',
        'pod_reference', 'delivery_token', 'notes', 'created_by',
        'region', 'optimized_route', 'route_order_source', 'route_polyline', 'estimated_km',
        'estimated_duration_minutes', 'delivery_cost', 'cost_per_delivery',
        'freight_weight_amount', 'freight_weight_unit', 'freight_weight_charge_mode',
        'freight_weight_quantity', 'freight_weight_breakdown',
        'total_weight_kg', 'total_volume_m3',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at'        => 'datetime',
            'delivered_at'      => 'datetime',
            'optimized_route'   => 'array',
            'estimated_km'      => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
            'delivery_cost'     => 'decimal:2',
            'cost_per_delivery' => 'decimal:2',
            'freight_weight_amount' => 'decimal:2',
            'freight_weight_unit' => 'decimal:4',
            'freight_weight_quantity' => 'decimal:3',
            'freight_weight_breakdown' => 'array',
            'total_weight_kg'   => 'decimal:3',
            'total_volume_m3'   => 'decimal:4',
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

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function saleOrders()
    {
        return $this->belongsToMany(SaleOrder::class, 'shipment_sale_order')
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
            ->orderBy('shipment_sale_order.delivery_sequence');
    }

    public function occurrences()
    {
        return $this->hasMany(ShipmentOccurrence::class)->latest();
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
