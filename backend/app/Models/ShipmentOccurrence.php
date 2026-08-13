<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentOccurrence extends Model
{
    protected $fillable = [
        'shipment_id', 'sale_order_id', 'type', 'description', 'occurred_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }
}
