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
        'ibge_code',
        'name',
        'is_capital',
    ];

    protected $casts = [
        'is_capital' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function scopeByStateUf($query, string $uf)
    {
        return $query->whereHas('state', function ($q) use ($uf) {
            $q->where('uf', strtoupper($uf));
        });
    }

    public function scopeByIbgeCode($query, string $ibgeCode)
    {
        return $query->where('ibge_code', $ibgeCode);
    }

    public function scopeCapitals($query)
    {
        return $query->where('is_capital', true);
    }
}
