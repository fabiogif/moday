<?php

namespace App\Models;

use App\Rules\Product\ProductCatalogVisibilityRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'flag', 'description', 'image', 'tenant_id', 'is_active',
        'brand', 'sku', 'barcode', 'weight', 'height', 'width', 'depth',
        // Pricing
        'price', 'price_cost', 'promotional_price', 'price_minimum',
        // Classification
        'product_type', 'requires_prescription', 'controlled_substance', 'storage_temperature',
        // Fiscal
        'tax_ncm', 'tax_cst', 'tax_rate',
        // Stock
        'qtd_stock', 'unit_of_measure', 'units_per_box', 'min_stock', 'max_stock', 'safety_stock', 'reorder_point',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_prescription' => 'boolean',
            'controlled_substance' => 'boolean',
            'price' => 'decimal:2',
            'price_cost' => 'decimal:2',
            'promotional_price' => 'decimal:2',
            'price_minimum' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'weight' => 'decimal:3',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'depth' => 'decimal:2',
            'min_stock'    => 'integer',
            'max_stock'    => 'integer',
            'safety_stock' => 'integer',
            'reorder_point' => 'integer',
            'units_per_box' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Accessor para garantir que variations sempre retorna array
     */
    public function getVariationsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($value) ? $value : [];
    }

    /**
     * Accessor para garantir que optionals sempre retorna array
     */
    public function getOptionalsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($value) ? $value : [];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (empty($model->flag)) {
                $model->flag = Str::kebab($model->name);
            }
        });
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function availableBatches()
    {
        return $this->hasMany(Batch::class)->fefo();
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function priceTableItems()
    {
        return $this->hasMany(PriceTableItem::class);
    }

    /** Stock actually available for sale (respects safety_stock reserve) */
    public function availableStock(): int
    {
        return max(0, ($this->qtd_stock ?? 0) - ($this->safety_stock ?? 0));
    }

    public function isLowStock(): bool
    {
        return $this->availableStock() <= ($this->min_stock ?? 0);
    }

    public function needsReorder(): bool
    {
        return $this->availableStock() <= ($this->reorder_point ?? 0);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('qtd_stock', '>', 0);
    }

    /**
     * Cardápio público, PDV e fluxos de venda.
     */
    public function scopeVisibleInCatalog(Builder $query): Builder
    {
        return ProductCatalogVisibilityRule::apply($query);
    }

    public function isVisibleInCatalog(): bool
    {
        return ProductCatalogVisibilityRule::passes($this);
    }

    /**
     * Get similar products based on categories
     */
    public function similarProducts($limit = 5)
    {
        $categoryIds = $this->categories()->pluck('categories.id');
        
        return self::query()
            ->where('id', '!=', $this->id)
            ->where('tenant_id', $this->tenant_id)
            ->visibleInCatalog()
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->with('categories')
            ->limit($limit)
            ->get();
    }

    /**
     * Get tenant relationship
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
