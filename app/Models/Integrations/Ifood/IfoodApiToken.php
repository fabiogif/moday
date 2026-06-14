<?php

namespace App\Models\Integrations\Ifood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IfoodApiToken extends Model
{
    use HasFactory;

    protected $table = 'ifood_api_tokens';

    protected $fillable = [
        'tenant_id',
        'access_token',
        'refresh_token',
        'token_type',
        'scope',
        'expires_at',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}

