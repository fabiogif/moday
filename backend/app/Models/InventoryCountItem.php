<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCountItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'inventory_count_id', 'product_id', 'batch_id',
        'system_quantity', 'counted_quantity', 'variance', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity'  => 'decimal:3',
            'counted_quantity' => 'decimal:3',
            'variance'         => 'decimal:3',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
