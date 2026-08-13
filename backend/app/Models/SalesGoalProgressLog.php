<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalesGoalProgressLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'sales_goal_id',
        'sale_order_id',
        'previous_value',
        'added_value',
        'new_value',
        'event_type',
        'created_at',
    ];

    protected $casts = [
        'previous_value' => 'decimal:2',
        'added_value' => 'decimal:2',
        'new_value' => 'decimal:2',
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
        });
    }

    public function salesGoal()
    {
        return $this->belongsTo(SalesGoal::class);
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }
}
