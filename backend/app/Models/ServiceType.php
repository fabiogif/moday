<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Database\Factories\ServiceTypeFactory;

class ServiceType extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ServiceTypeFactory::new();
    }
    
    protected $fillable = [
        'uuid',
        'identify',
        'name',
        'slug',
        'description',
        'is_active',
        'requires_address',
        'requires_table',
        'available_in_menu',
        'order_position',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_address' => 'boolean',
            'requires_table' => 'boolean',
            'available_in_menu' => 'boolean',
            'order_position' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
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
            if (empty($model->identify)) {
                $model->identify = Str::slug($model->name);
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Relacionamento com pedidos
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'service_type_id');
    }

    /**
     * Scope para tipos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para tipos disponíveis no menu
     */
    public function scopeAvailableInMenu($query)
    {
        return $query->where('available_in_menu', true)->where('is_active', true);
    }

    /**
     * Scope ordenado por posição
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_position', 'asc')->orderBy('name', 'asc');
    }
}






