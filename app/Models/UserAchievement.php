<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserAchievement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'achievement_definition_id',
        'unlocked_at',
        'sale_order_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
            if (empty($model->unlocked_at)) {
                $model->unlocked_at = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function achievementDefinition()
    {
        return $this->belongsTo(AchievementDefinition::class);
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }
}
