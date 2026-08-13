<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'description', 'type', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    const TYPES = [
        'padrao'             => 'Padrão',
        'cliente_especifico' => 'Cliente Específico',
        'canal'              => 'Por Canal',
        'volume'             => 'Por Volume',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items()
    {
        return $this->hasMany(PriceTableItem::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function getPriceForProduct(int $productId, float $quantity = 1): ?float
    {
        return $this->items()
            ->where('product_id', $productId)
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('min_quantity', 'desc')
            ->value('price');
    }
}
