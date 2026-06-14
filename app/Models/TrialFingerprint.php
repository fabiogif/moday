<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialFingerprint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document', 'doc_type', 'tenant_id',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function hasUsedTrial(string $document): bool
    {
        return static::where('document', preg_replace('/\D/', '', $document))->exists();
    }

    public static function register(string $document, int $tenantId): static
    {
        $digits  = preg_replace('/\D/', '', $document);
        $docType = strlen($digits) === 11 ? 'cpf' : 'cnpj';

        return static::firstOrCreate(
            ['document' => $digits],
            ['doc_type' => $docType, 'tenant_id' => $tenantId]
        );
    }
}
