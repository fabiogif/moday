<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SalesGoal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'description',
        'goal_type',
        'target_user_id',
        'target_profile_id',
        'target_product_id',
        'period_type',
        'period_start',
        'period_end',
        'target_value',
        'current_value',
        'completion_percent',
        'status',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'completion_percent' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function targetProfile()
    {
        return $this->belongsTo(Profile::class, 'target_profile_id');
    }

    public function targetProduct()
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progressLogs()
    {
        return $this->hasMany(SalesGoalProgressLog::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('goal_type', $type);
    }

    public function scopeForSeller($query, int $userId)
    {
        return $query->where('goal_type', 'seller')
                     ->where('target_user_id', $userId);
    }

    public function scopeInPeriod($query, $date)
    {
        return $query->where('period_start', '<=', $date)
                     ->where('period_end', '>=', $date);
    }

    public function getProgressPercentage(): float
    {
        if ($this->target_value == 0) {
            return 0;
        }

        return min(round(($this->current_value / $this->target_value) * 100, 2), 100);
    }

    public function isCompleted(): bool
    {
        return $this->current_value >= $this->target_value;
    }
}
