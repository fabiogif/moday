<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'is_capital',
    ];

    protected $casts = [
        'is_capital' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the state that owns the city
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Scope to find cities by state UF
     */
    public function scopeByStateUf($query, string $uf)
    {
        return $query->whereHas('state', function ($q) use ($uf) {
            $q->where('uf', strtoupper($uf));
        });
    }

    /**
     * Scope to find only capital cities
     */
    public function scopeCapitals($query)
    {
        return $query->where('is_capital', true);
    }
}

