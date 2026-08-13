<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamificationPointLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'points',
        'balance_after',
        'source_type',
        'source_id',
        'description',
        'created_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
