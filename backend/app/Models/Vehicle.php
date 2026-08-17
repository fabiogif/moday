<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'tenant_id',
        'plate',
        'type',
        'brand',
        'model',
        'year',
        'capacity_kg',
        'capacity_m3',
        'km_per_liter',
        'is_active',
    ];

    protected $casts = [
        'capacity_kg'  => 'decimal:2',
        'capacity_m3'  => 'decimal:4',
        'km_per_liter' => 'decimal:2',
        'is_active'    => 'boolean',
        'year'         => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([$this->brand, $this->model, $this->plate]);
        return implode(' ', $parts) ?: $this->plate;
    }
}
