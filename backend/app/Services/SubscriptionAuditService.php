<?php

namespace App\Services;

use App\Models\SubscriptionEvent;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class SubscriptionAuditService
{
    public function log(
        Tenant  $tenant,
        string  $eventType,
        ?string $statusBefore = null,
        ?string $statusAfter  = null,
        ?int    $planIdBefore  = null,
        ?int    $planIdAfter   = null,
        ?array  $mpPayload     = null,
        ?array  $metadata      = null,
        ?int    $userId        = null
    ): void {
        try {
            SubscriptionEvent::create([
                'tenant_id'     => $tenant->id,
                'user_id'       => $userId ?? auth()->id(),
                'event_type'    => $eventType,
                'status_before' => $statusBefore,
                'status_after'  => $statusAfter,
                'plan_id_before'=> $planIdBefore,
                'plan_id_after' => $planIdAfter,
                'mp_payload'    => $mpPayload,
                'ip_address'    => Request::ip(),
                'metadata'      => $metadata,
            ]);
        } catch (\Throwable $e) {
            // Never let audit failure break the main flow
            Log::error('SubscriptionAudit: falha ao registrar evento', [
                'tenant_id'  => $tenant->id,
                'event_type' => $eventType,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function trialStarted(Tenant $tenant): void
    {
        $this->log($tenant, SubscriptionEvent::TRIAL_STARTED,
            statusAfter: 'trial');
    }

    public function trialExpired(Tenant $tenant): void
    {
        $this->log($tenant, SubscriptionEvent::TRIAL_EXPIRED,
            statusBefore: 'trial', statusAfter: 'expired');
    }

    public function subscriptionActivated(Tenant $tenant, int $planId, ?array $mpPayload = null): void
    {
        $this->log($tenant, SubscriptionEvent::SUBSCRIPTION_ACTIVATED,
            statusAfter: 'active', planIdAfter: $planId, mpPayload: $mpPayload);
    }

    public function paymentReceived(Tenant $tenant, ?array $mpPayload = null): void
    {
        $this->log($tenant, SubscriptionEvent::PAYMENT_RECEIVED, mpPayload: $mpPayload);
    }

    public function paymentFailed(Tenant $tenant, ?array $mpPayload = null): void
    {
        $this->log($tenant, SubscriptionEvent::PAYMENT_FAILED, mpPayload: $mpPayload);
    }

    public function upgrade(Tenant $tenant, int $fromPlanId, int $toPlanId, ?array $mpPayload = null): void
    {
        $this->log($tenant, SubscriptionEvent::UPGRADE,
            statusBefore: 'active', statusAfter: 'active',
            planIdBefore: $fromPlanId, planIdAfter: $toPlanId,
            mpPayload: $mpPayload);
    }

    public function downgradeScheduled(Tenant $tenant, int $fromPlanId, int $toPlanId, string $effectiveDate): void
    {
        $this->log($tenant, SubscriptionEvent::DOWNGRADE_SCHEDULED,
            planIdBefore: $fromPlanId, planIdAfter: $toPlanId,
            metadata: ['effective_date' => $effectiveDate]);
    }

    public function downgradeApplied(Tenant $tenant, int $fromPlanId, int $toPlanId): void
    {
        $this->log($tenant, SubscriptionEvent::DOWNGRADE_APPLIED,
            planIdBefore: $fromPlanId, planIdAfter: $toPlanId);
    }

    public function downgradeCancelled(Tenant $tenant, int $planId): void
    {
        $this->log($tenant, SubscriptionEvent::DOWNGRADE_CANCELLED,
            metadata: ['cancelled_plan_id' => $planId]);
    }

    public function cancellationRequested(Tenant $tenant, ?string $periodEnd = null): void
    {
        $this->log($tenant, SubscriptionEvent::CANCELLATION_REQUESTED,
            metadata: ['access_until' => $periodEnd]);
    }

    public function cancelled(Tenant $tenant): void
    {
        $this->log($tenant, SubscriptionEvent::CANCELLED,
            statusBefore: 'active', statusAfter: 'cancelled');
    }

    public function reactivated(Tenant $tenant, int $planId): void
    {
        $this->log($tenant, SubscriptionEvent::REACTIVATED,
            statusAfter: 'active', planIdAfter: $planId);
    }

    public function dunningStarted(Tenant $tenant): void
    {
        $this->log($tenant, SubscriptionEvent::DUNNING_STARTED);
    }

    public function dunningNotification(Tenant $tenant, int $day, string $channel): void
    {
        $this->log($tenant, SubscriptionEvent::DUNNING_NOTIFICATION,
            metadata: ['day' => $day, 'channel' => $channel]);
    }

    public function delinquent(Tenant $tenant): void
    {
        $this->log($tenant, SubscriptionEvent::DELINQUENT,
            statusBefore: 'pending', statusAfter: 'delinquent');
    }

    public function suspended(Tenant $tenant): void
    {
        $this->log($tenant, SubscriptionEvent::SUSPENDED,
            statusAfter: 'suspended');
    }

    public function webhookReceived(Tenant $tenant, string $topic, array $payload): void
    {
        $this->log($tenant, SubscriptionEvent::WEBHOOK_RECEIVED,
            mpPayload: ['topic' => $topic, 'data' => $payload]);
    }

    public function adminOverride(Tenant $tenant, string $fromStatus, string $toStatus, int $adminId, string $reason): void
    {
        $this->log($tenant, SubscriptionEvent::ADMIN_OVERRIDE,
            statusBefore: $fromStatus, statusAfter: $toStatus,
            metadata: ['reason' => $reason], userId: $adminId);
    }
}
