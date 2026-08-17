<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitPresentedProduct extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'visit_id', 'product_id', 'was_ordered', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'was_ordered' => 'boolean',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
