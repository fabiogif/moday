<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GamificationProfile extends Model
{
    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'total_points',
        'current_streak_days',
        'best_streak_days',
        'last_activity_date',
        'achievements_count',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'current_streak_days' => 'integer',
        'best_streak_days' => 'integer',
        'last_activity_date' => 'date',
        'achievements_count' => 'integer',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
