<?php

namespace App\Models\Integrations\Ifood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IfoodApiLog extends Model
{
    use HasFactory;

    protected $table = 'ifood_api_logs';

    protected $fillable = [
        'tenant_id',
        'level',
        'context',
        'action',
        'payload',
        'message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}

