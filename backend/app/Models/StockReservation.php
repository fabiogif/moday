<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    protected $fillable = [
        'tenant_id', 'sale_order_id', 'sale_order_item_id',
        'batch_id', 'quantity', 'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function saleOrderItem()
    {
        return $this->belongsTo(SaleOrderItem::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
