<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = ['uuid', 'name', 'url', 'description', 'tenant_id', 'status', 'is_active', 'order'];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
            'is_active' => 'boolean',
            'order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = $value ?? '';
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
            if (empty($model->url)) {
                $model->url = Str::slug($model->name);
            }
            if (empty($model->status)) {
                $model->status = 'A'; // Status padrão 'Ativo'
            }
            if (!isset($model->order)) {
                $model->order = 0;
            }
            if (!isset($model->description)) {
                $model->description = '';
            }
        });
    }


    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

}
