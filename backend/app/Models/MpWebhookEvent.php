<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpWebhookEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'mp_event_id', 'topic', 'payload', 'status', 'error_message', 'tenant_id',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function isDuplicate(string $mpEventId): bool
    {
        return static::where('mp_event_id', $mpEventId)->exists();
    }
}
