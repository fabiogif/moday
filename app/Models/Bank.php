<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'full_name',
        'ispb',
        'supports_pix',
        'is_active',
    ];

    protected $casts = [
        'supports_pix' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithPix($query)
    {
        return $query->where('supports_pix', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Get bank by code or fail
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }
}

