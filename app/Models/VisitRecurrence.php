<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VisitRecurrence extends Model
{
    use HasFactory;

    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'custom'];

    protected $fillable = [
        'tenant_id', 'uuid', 'client_id', 'user_id',
        'frequency', 'interval_count', 'days_of_week', 'day_of_month',
        'scheduled_start_time', 'scheduled_end_time', 'type', 'priority',
        'starts_on', 'ends_on', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function (self $recurrence) {
            $recurrence->uuid ??= (string) Str::uuid();
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class, 'recurrence_id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
