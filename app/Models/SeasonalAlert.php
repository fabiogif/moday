<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonalAlert extends Model
{
    protected $fillable = [
        'tenant_id', 'type', 'priority', 'title', 'body',
        'metadata', 'reference_date', 'acknowledged_at', 'acknowledged_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata'        => 'array',
            'reference_date'  => 'date',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }
}
