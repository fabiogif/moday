<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'order_position',
        'is_initial',
        'is_final',
        'is_active',
        'integration_codes',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
        'is_active' => 'boolean',
        'order_position' => 'integer',
        'integration_codes' => 'array',
    ];

    protected $hidden = [
        'id',
        'tenant_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
            if (!$model->slug) {
                $model->slug = static::generateUniqueSlug($model->name, $model->tenant_id);
            }
        });

        static::updating(function ($model) {
            // Se o nome foi alterado, atualizar o slug (mas manter unicidade)
            if ($model->isDirty('name') && !$model->isDirty('slug')) {
                $model->slug = static::generateUniqueSlug($model->name, $model->tenant_id, $model->uuid);
            }
        });
    }

    /**
     * Gera um slug único para o tenant
     * 
     * @param string $name
     * @param int $tenantId
     * @param string|null $excludeUuid UUID do registro atual (para atualizações)
     * @return string
     */
    protected static function generateUniqueSlug(string $name, int $tenantId, ?string $excludeUuid = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Verificar se o slug já existe para este tenant
        while (static::where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->when($excludeUuid, function ($query) use ($excludeUuid) {
                return $query->where('uuid', '!=', $excludeUuid);
            })
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Relacionamentos
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_status_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_position');
    }

    // Métodos auxiliares
    public function canBeDeleted(): bool
    {
        return $this->orders()->count() === 0;
    }
}

