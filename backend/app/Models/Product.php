<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'name', 'flag', 'price', 'price_cost', 'promotional_price', 
        'description', 'image', 'tenant_id', 'qtd_stock', 'is_active',
        'brand', 'sku', 'weight', 'height', 'width', 'depth', 
        'shipping_info', 'warehouse_location', 'variations'
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'decimal:2',
            'price_cost' => 'decimal:2',
            'promotional_price' => 'decimal:2',
            'weight' => 'decimal:3',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'depth' => 'decimal:2',
            'variations' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Set the variations attribute.
     * Ensures variations are stored as valid JSON array
     */
    public function setVariationsAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $this->attributes['variations'] = json_encode(is_array($decoded) ? $decoded : []);
        } elseif (is_array($value)) {
            $this->attributes['variations'] = json_encode($value);
        } else {
            $this->attributes['variations'] = json_encode([]);
        }
    }

    /**
     * Get the variations attribute.
     * Ensures variations are returned as array
     */
    public function getVariationsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
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

    /**
     * Get similar products based on categories
     */
    public function similarProducts($limit = 5)
    {
        $categoryIds = $this->categories()->pluck('categories.id');
        
        return self::where('id', '!=', $this->id)
            ->where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
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
