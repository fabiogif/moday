<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'event_type',
        'email_enabled',
        'push_enabled',
        'sms_enabled',
        'frequency',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: By user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: By event type
     */
    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Check if channel is enabled
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match($channel) {
            'email' => $this->email_enabled,
            'push' => $this->push_enabled,
            'sms' => $this->sms_enabled,
            default => false,
        };
    }

    /**
     * Check if should send now (considering quiet hours)
     */
    public function shouldSendNow(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return true;
        }

        $now = now()->format('H:i');
        $start = $this->quiet_hours_start;
        $end = $this->quiet_hours_end;

        // Se quiet hours não atravessa meia-noite
        if ($start < $end) {
            return $now < $start || $now > $end;
        }

        // Se quiet hours atravessa meia-noite
        return $now > $end && $now < $start;
    }

    /**
     * Default event types
     */
    public static function getEventTypes(): array
    {
        return [
            'user_created',
            'user_updated',
            'user_activated',
            'user_deactivated',
            'order_created',
            'order_status_changed',
            'order_completed',
            'order_cancelled',
            'product_created',
            'product_updated',
            'product_stock_low',
            'product_out_of_stock',
            'product_inactivated',
            'client_created',
            'client_first_purchase',
            'client_inactivated',
        ];
    }
}

