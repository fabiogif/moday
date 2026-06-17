<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'subdomain', 'cnpj', 'email', 'phone', 'address',
        'city', 'state', 'zipcode', 'country', 'url', 'logo', 'active',
        'is_active', 'settings', 'subscription', 'expire_at', 'plan_id', 'uuid',
        'subscription_id', 'subscription_plan', 'subscription_active',
        'subscription_suspended', 'account_status', 'trial_started_at',
        'trial_expires_at', 'activated_at', 'last_payment_at', 'admin_notes',
        'users_limit', 'messages_limit', 'messages_sent', 'is_blocked',
        'blocked_at', 'blocked_reason', 'mrr', 'last_login_at', 'total_logins',
        'segment', 'nfe_series', 'nfe_next_number', 'fiscal_integration_provider',
        'fiscal_integration_config',
        // Subscription v2
        'mp_customer_id', 'mp_subscription_id', 'mp_subscription_status',
        'current_period_start', 'current_period_end', 'next_billing_date',
        'cancellation_requested_at', 'cancelled_at',
        'scheduled_downgrade_plan_id', 'scheduled_downgrade_at',
        'scheduled_downgrade_attempts', 'dunning_started_at', 'dunning_day',
        'data_deletion_scheduled_at',
        // WhatsApp / Evolution API
        'evolution_instance',
    ];

    protected function casts(): array
    {
        return [
            'is_active'                  => 'boolean',
            'is_blocked'                 => 'boolean',
            'subscription_active'        => 'boolean',
            'subscription_suspended'     => 'boolean',
            'settings'                   => 'array',
            'fiscal_integration_config'  => 'array',
            'subscription'               => 'date',
            'expire_at'                  => 'date',
            'trial_started_at'           => 'datetime',
            'trial_expires_at'           => 'datetime',
            'activated_at'               => 'datetime',
            'last_payment_at'            => 'datetime',
            'blocked_at'                 => 'datetime',
            'last_login_at'              => 'datetime',
            'messages_sent'              => 'integer',
            'users_limit'                => 'integer',
            'messages_limit'             => 'integer',
            'total_logins'               => 'integer',
            'mrr'                        => 'decimal:2',
            'dunning_day'                => 'integer',
            'current_period_start'       => 'date',
            'current_period_end'         => 'date',
            'next_billing_date'          => 'date',
            'cancellation_requested_at'  => 'datetime',
            'cancelled_at'               => 'datetime',
            'scheduled_downgrade_at'     => 'date',
            'dunning_started_at'         => 'datetime',
            'data_deletion_scheduled_at' => 'datetime',
            'created_at'                 => 'datetime',
            'updated_at'                 => 'datetime',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTrialActive($query)
    {
        return $query->where('account_status', 'trial')
            ->where('trial_expires_at', '>', now());
    }

    public function scopeTrialExpired($query)
    {
        return $query->where('account_status', 'trial')
            ->where('trial_expires_at', '<=', now());
    }

    public function scopePaid($query)
    {
        return $query->where('account_status', 'active');
    }

    public function scopeInDunning($query)
    {
        return $query->whereNotNull('dunning_started_at')
            ->whereIn('account_status', ['pending', 'under_review', 'delinquent']);
    }

    public function scopePendingDeletion($query)
    {
        return $query->whereNotNull('data_deletion_scheduled_at')
            ->where('data_deletion_scheduled_at', '<=', now());
    }

    // ── Status checks ─────────────────────────────────────────────────────────

    public function isTrialActive(): bool
    {
        return $this->account_status === 'trial'
            && $this->trial_expires_at
            && $this->trial_expires_at->isFuture();
    }

    public function isTrialExpired(): bool
    {
        return $this->account_status === 'trial'
            && $this->trial_expires_at
            && $this->trial_expires_at->isPast();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->account_status === 'active';
    }

    public function isPending(): bool { return $this->account_status === 'pending'; }
    public function isUnderReview(): bool { return $this->account_status === 'under_review'; }
    public function isDelinquent(): bool { return $this->account_status === 'delinquent'; }
    public function isSuspended(): bool { return $this->account_status === 'suspended'; }
    public function isCancelled(): bool { return $this->account_status === 'cancelled'; }
    public function isExpired(): bool { return $this->account_status === 'expired'; }

    public function isInDunning(): bool
    {
        return $this->dunning_started_at !== null
            && in_array($this->account_status, ['pending', 'under_review', 'delinquent']);
    }

    public function isAccessBlocked(): bool
    {
        return in_array($this->account_status, ['delinquent', 'suspended', 'cancelled', 'expired'])
            || (bool) $this->is_blocked;
    }

    public function hasCancellationPending(): bool
    {
        return $this->cancellation_requested_at !== null && $this->cancelled_at === null;
    }

    public function hasScheduledDowngrade(): bool
    {
        return $this->scheduled_downgrade_plan_id !== null
            && $this->scheduled_downgrade_at !== null;
    }

    // ── Trial helpers ─────────────────────────────────────────────────────────

    public function trialDaysRemaining(): int
    {
        if (!$this->trial_expires_at) {
            return 0;
        }
        $days = now()->diffInDays($this->trial_expires_at, false);
        return max(0, (int) ceil($days));
    }

    public function isTrialExpiringSoon(): bool
    {
        return $this->isTrialActive() && $this->trialDaysRemaining() <= 3;
    }

    public function isOnFreePlan(): bool
    {
        $this->loadMissing('plan');
        return $this->plan !== null && $this->plan->isFree();
    }

    public function canAccess(): bool
    {
        if ($this->is_blocked) {
            return false;
        }
        if ($this->isOnFreePlan() && $this->hasActiveSubscription()) {
            return true;
        }
        return $this->isTrialActive() || $this->hasActiveSubscription();
    }

    public function dunningDaysElapsed(): int
    {
        if (!$this->dunning_started_at) {
            return 0;
        }
        return (int) $this->dunning_started_at->diffInDays(now());
    }

    // ── State transitions ─────────────────────────────────────────────────────

    public function startTrial(): void
    {
        $this->account_status   = 'trial';
        $this->trial_started_at = now();
        $this->trial_expires_at = now()->addDays((int) config('subscription.trial_days', 7));
        $this->save();
    }

    public function activateFreePlan(string $planName): void
    {
        $this->account_status    = 'active';
        $this->subscription_plan = $planName;
        $this->activated_at      = now();
        $this->trial_started_at  = null;
        $this->trial_expires_at  = null;
        $this->save();
    }

    public function activateSubscription(string $plan): void
    {
        $this->account_status     = 'active';
        $this->subscription_plan  = $plan;
        $this->activated_at       = now();
        $this->last_payment_at    = now();
        $this->dunning_started_at = null;
        $this->dunning_day        = 0;
        $this->save();
    }

    public function expireTrial(): void
    {
        $this->account_status = 'expired';
        $this->save();
    }

    public function markPending(): void
    {
        if (!$this->dunning_started_at) {
            $this->dunning_started_at = now();
            $this->dunning_day        = 0;
        }
        $this->account_status = 'pending';
        $this->save();
    }

    public function markDelinquent(): void
    {
        $this->account_status = 'delinquent';
        $this->is_blocked     = true;
        $this->blocked_at     = now();
        $this->blocked_reason = 'Inadimplência: 7 dias sem pagamento';
        $this->save();
    }

    public function suspend(): void
    {
        $this->account_status = 'suspended';
        $this->save();
    }

    public function requestCancellation(): void
    {
        $this->cancellation_requested_at = now();
        $this->save();
    }

    public function applyCancellation(): void
    {
        $this->account_status = 'cancelled';
        $this->cancelled_at   = now();
        $this->save();
    }

    public function reactivate(): void
    {
        $this->account_status            = 'active';
        $this->last_payment_at           = now();
        $this->is_blocked                = false;
        $this->blocked_at                = null;
        $this->blocked_reason            = null;
        $this->dunning_started_at        = null;
        $this->dunning_day               = 0;
        $this->cancellation_requested_at = null;
        $this->cancelled_at              = null;
        $this->save();
    }

    public function scheduleDowngrade(int $planId, \Carbon\Carbon $effectiveDate): void
    {
        $this->scheduled_downgrade_plan_id  = $planId;
        $this->scheduled_downgrade_at       = $effectiveDate;
        $this->scheduled_downgrade_attempts = 0;
        $this->save();
    }

    public function clearScheduledDowngrade(): void
    {
        $this->scheduled_downgrade_plan_id  = null;
        $this->scheduled_downgrade_at       = null;
        $this->scheduled_downgrade_attempts = 0;
        $this->save();
    }

    // ── API representation ────────────────────────────────────────────────────

    public function toTrialStatusArray(): array
    {
        return [
            'is_trial'             => $this->account_status === 'trial',
            'is_active'            => $this->canAccess(),
            'is_expired'           => $this->isExpired() || $this->isTrialExpired(),
            'days_remaining'       => $this->trialDaysRemaining(),
            'expires_at'           => $this->trial_expires_at?->format('Y-m-d H:i:s'),
            'is_expiring_soon'     => $this->isTrialExpiringSoon(),
            'needs_payment'        => !$this->isOnFreePlan() && $this->account_status !== 'active',
            'account_status'       => $this->account_status,
            'is_delinquent'        => $this->isDelinquent(),
            'is_suspended'         => $this->isSuspended(),
            'is_cancelled'         => $this->isCancelled(),
            'is_in_dunning'        => $this->isInDunning(),
            'dunning_day'          => $this->dunning_day ?? 0,
            'current_period_end'   => $this->current_period_end?->format('Y-m-d'),
            'next_billing_date'    => $this->next_billing_date?->format('Y-m-d'),
            'cancellation_pending' => $this->hasCancellationPending(),
            'scheduled_downgrade'  => $this->hasScheduledDowngrade() ? [
                'plan_id'        => $this->scheduled_downgrade_plan_id,
                'effective_date' => $this->scheduled_downgrade_at?->format('Y-m-d'),
            ] : null,
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function scheduledDowngradePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'scheduled_downgrade_plan_id');
    }

    public function subscriptionEvents(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function dunningEvents(): HasMany
    {
        return $this->hasMany(SubscriptionDunningEvent::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function trialFingerprint()
    {
        return $this->hasOne(TrialFingerprint::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
