<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeocodedAddress extends Model
{
    protected $fillable = [
        'tenant_id',
        'address_hash',
        'formatted_address',
        'latitude',
        'longitude',
        'source',
        'geocoded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'geocoded_at' => 'datetime',
        ];
    }
}
