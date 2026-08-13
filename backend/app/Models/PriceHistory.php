<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    protected $fillable = [
        'tenant_id', 'product_id', 'changed_by',
        'field', 'old_value', 'new_value', 'change_pct', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'old_value'  => 'decimal:4',
            'new_value'  => 'decimal:4',
            'change_pct' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
