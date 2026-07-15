<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_order_id', 'product_id', 'batch_id', 'item_type',
        'quantity', 'quantity_picked', 'picked_at', 'picked_by',
        'unit_price', 'discount_percent', 'offer_rule_id',
        'subtotal', 'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity'         => 'decimal:3',
            'quantity_picked'  => 'decimal:3',
            'picked_at'        => 'datetime',
            'unit_price'       => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'subtotal'         => 'decimal:2',
            'tax_amount'       => 'decimal:2',
        ];
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function offerRule()
    {
        return $this->belongsTo(OfferRule::class);
    }

    public function getLineTotalAttribute(): float
    {
        $gross = $this->quantity * $this->unit_price;
        $discount = $gross * ($this->discount_percent / 100);
        return $gross - $discount + $this->tax_amount;
    }
}
