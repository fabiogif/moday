<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AchievementDefinition extends Model
{
    protected $fillable = [
        'uuid',
        'tenant_id',
        'key',
        'name',
        'description',
        'icon',
        'badge_color',
        'category',
        'trigger_type',
        'trigger_config',
        'points_reward',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'points_reward' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhereNull('tenant_id');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
