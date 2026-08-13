<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceTableItem extends Model
{
    protected $fillable = [
        'price_table_id', 'product_id', 'price', 'min_quantity',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'min_quantity' => 'integer',
        ];
    }

    public function priceTable()
    {
        return $this->belongsTo(PriceTable::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
