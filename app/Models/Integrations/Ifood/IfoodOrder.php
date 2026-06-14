<?php

namespace App\Models\Integrations\Ifood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IfoodOrder extends Model
{
    use HasFactory;

    protected $table = 'ifood_orders';

    protected $fillable = [
        'tenant_id',
        'external_order_id',
        'display_id',
        'order_id',
        'status',
        'order_type',
        'order_timing',
        'sales_channel',
        'category',
        'total_amount',
        'items_total',
        'delivery_fee',
        'benefits_total',
        'additional_fees_total',
        'customer_name',
        'customer_phone',
        'customer_document_number',
        'customer_orders_count',
        'customer_segmentation',
        'delivery_address',
        'preparation_start_at',
        'is_test',
        'extra_info',
        'merchant_identifier',
        'merchant_name',
        'benefits',
        'additional_fees',
        'payments',
        'picking',
        'delivery',
        'takeout',
        'dinein',
        'schedule',
        'additional_info',
        'raw_payload',
        'received_at',
        'last_synced_at',
    ];

    protected $casts = [
        'delivery_address' => 'array',
        'raw_payload' => 'array',
        'received_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'items_total' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'benefits_total' => 'decimal:2',
        'additional_fees_total' => 'decimal:2',
        'customer_orders_count' => 'integer',
        'is_test' => 'boolean',
        'preparation_start_at' => 'datetime',
        'benefits' => 'array',
        'additional_fees' => 'array',
        'payments' => 'array',
        'picking' => 'array',
        'delivery' => 'array',
        'takeout' => 'array',
        'dinein' => 'array',
        'schedule' => 'array',
        'additional_info' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(IfoodOrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(IfoodOrderStatusLog::class);
    }
}

