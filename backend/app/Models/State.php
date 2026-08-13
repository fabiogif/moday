<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'ibge_code',
        'uf',
        'name',
        'region',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function scopeByUf($query, string $uf)
    {
        return $query->where('uf', strtoupper($uf));
    }

    public function scopeByIbgeCode($query, string $ibgeCode)
    {
        return $query->where('ibge_code', $ibgeCode);
    }
}
