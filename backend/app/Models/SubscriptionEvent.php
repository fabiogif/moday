<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'tenant_id', 'user_id', 'event_type',
        'status_before', 'status_after',
        'plan_id_before', 'plan_id_after',
        'mp_payload', 'ip_address', 'metadata', 'created_at',
    ];

    protected $casts = [
        'mp_payload' => 'array',
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    // Immutable — no update or delete allowed
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });

        static::updating(fn() => false);
        static::deleting(fn() => false);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Event type constants
    const TRIAL_STARTED          = 'trial_started';
    const TRIAL_EXPIRED          = 'trial_expired';
    const SUBSCRIPTION_CREATED   = 'subscription_created';
    const SUBSCRIPTION_ACTIVATED = 'subscription_activated';
    const PAYMENT_RECEIVED       = 'payment_received';
    const PAYMENT_FAILED         = 'payment_failed';
    const UPGRADE                = 'upgrade';
    const DOWNGRADE_SCHEDULED    = 'downgrade_scheduled';
    const DOWNGRADE_APPLIED      = 'downgrade_applied';
    const DOWNGRADE_CANCELLED    = 'downgrade_cancelled';
    const CANCELLATION_REQUESTED = 'cancellation_requested';
    const CANCELLED              = 'cancelled';
    const REACTIVATED            = 'reactivated';
    const DUNNING_STARTED        = 'dunning_started';
    const DUNNING_NOTIFICATION   = 'dunning_notification';
    const DELINQUENT             = 'delinquent';
    const SUSPENDED              = 'suspended';
    const DATA_ANONYMIZED        = 'data_anonymized';
    const DATA_DELETED           = 'data_deleted';
    const WEBHOOK_RECEIVED       = 'webhook_received';
    const ADMIN_OVERRIDE         = 'admin_override';
}
