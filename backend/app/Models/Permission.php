<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'action',
        'resource',
        'is_active',
        'tenant_id'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Relacionamento com tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Get the users that have this permission directly.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions');
    }

    /**
     * Get the profiles that have this permission.
     */
    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'permission_profiles');
    }

    /**
     * Scope a query to only include active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by module.
     */
    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope a query to filter by action.
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to filter by resource.
     */
    public function scopeResource($query, $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope para permissões de um tenant específico
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Check if permission is for a specific module.
     */
    public function isForModule(string $module): bool
    {
        return $this->module === $module;
    }

    /**
     * Check if permission is for a specific action.
     */
    public function isForAction(string $action): bool
    {
        return $this->action === $action;
    }

    /**
     * Check if permission is for a specific resource.
     */
    public function isForResource(string $resource): bool
    {
        return $this->resource === $resource;
    }

    /**
     * Get permission identifier.
     */
    public function getIdentifier(): string
    {
        return $this->module . '.' . $this->action . '.' . $this->resource;
    }
}