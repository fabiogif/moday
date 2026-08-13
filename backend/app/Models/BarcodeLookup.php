<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarcodeLookup extends Model
{
    protected $fillable = [
        'barcode',
        'source',
        'name',
        'brand',
        'category',
        'unit_of_measure',
        'weight',
        'volume',
        'image_url',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
            'volume' => 'decimal:4',
            'raw_payload' => 'array',
        ];
    }
}
