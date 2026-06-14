<?php

namespace App\Models\Integrations\Ifood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class IfoodOauthSession extends Model
{
    use HasFactory;

    protected $table = 'ifood_oauth_sessions';

    protected $fillable = [
        'tenant_id',
        'user_code',
        'authorization_code_verifier',
        'verification_url',
        'verification_url_complete',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function setAuthorizationCodeVerifierAttribute(string $value): void
    {
        $this->attributes['authorization_code_verifier'] = Crypt::encryptString($value);
    }

    public function getAuthorizationCodeVerifierAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

