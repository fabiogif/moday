<?php

namespace App\Models\Integrations\Ifood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IfoodOrderItem extends Model
{
    use HasFactory;

    protected $table = 'ifood_order_items';

    protected $fillable = [
        'ifood_order_id',
        'sequence',
        'external_item_id',
        'unique_id',
        'external_code',
        'sku',
        'ean',
        'name',
        'quantity',
        'quantity_value',
        'unit',
        'unit_price',
        'price',
        'total_price',
        'options_price',
        'customization_price',
        'observations',
        'options',
        'scale_prices',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'options' => 'array',
        'scale_prices' => 'array',
        'unit_price' => 'decimal:2',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'options_price' => 'decimal:2',
        'customization_price' => 'decimal:2',
        'quantity_value' => 'decimal:3',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(IfoodOrder::class, 'ifood_order_id');
    }
}

