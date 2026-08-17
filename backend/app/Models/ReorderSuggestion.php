<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReorderSuggestion extends Model
{
    protected $fillable = [
        'tenant_id', 'product_id', 'supplier_id',
        'daily_avg_sales', 'days_of_coverage', 'lead_time_days',
        'suggested_quantity', 'estimated_cost', 'urgency',
        'calculated_at', 'is_dismissed', 'po_created', 'purchase_order_id',
    ];

    protected $casts = [
        'daily_avg_sales'   => 'decimal:4',
        'estimated_cost'    => 'decimal:2',
        'is_dismissed'      => 'boolean',
        'po_created'        => 'boolean',
        'calculated_at'     => 'datetime',
    ];

    public function product()  { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
}
