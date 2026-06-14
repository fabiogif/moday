<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionDunningEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'dunning_day', 'channel', 'success', 'error_message',
    ];

    protected $casts = [
        'dunning_day' => 'integer',
        'success'     => 'boolean',
        'sent_at'     => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
