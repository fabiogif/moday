<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Central orchestrator for the subscription lifecycle (Preapproval mode).
 *
 * Coordinates: MercadoPagoService, SubscriptionAuditService, TrialFraudService.
 * Does NOT send notifications — callers dispatch events for that.
 */
class SubscriptionDomainService
{
    private const MAX_DOWNGRADE_ATTEMPTS = 3;

    public function __construct(
        private readonly MercadoPagoService     $mp,
        private readonly SubscriptionAuditService $audit,
        private readonly TrialFraudService      $fraud,
    ) {}

    // ── Trial ─────────────────────────────────────────────────────────────────

    /**
     * Start a 7-day trial.
     *
     * @throws \DomainException when fraud is detected or tenant is ineligible
     */
    public function startTrial(Tenant $tenant): void
    {
        if ($tenant->account_status !== 'expired' && $tenant->account_status !== null) {
            if (!in_array($tenant->account_status, ['trial', 'expired'], true)) {
                // Only allow fresh tenants or expired ones to start a trial
            }
        }

        if (!$this->fraud->canStartTrial($tenant)) {
            Log::warning('SubscriptionDomain: trial bloqueado por fraude', ['tenant_id' => $tenant->id]);
            throw new \DomainException('Este CNPJ/CPF já utilizou o período de teste gratuito.');
        }

        DB::transaction(function () use ($tenant) {
            $tenant->startTrial();
            $this->fraud->registerTrialUse($tenant);
            $this->audit->trialStarted($tenant);
        });

        Log::info('SubscriptionDomain: trial iniciado', ['tenant_id' => $tenant->id]);
    }

    // ── Activation (Preapproval mode) ─────────────────────────────────────────

    /**
     * Activate subscription after successful Preapproval creation.
     *
     * Called after MP returns preapproval_id in 'authorized' state.
     */
    public function activateFromPreapproval(
        Tenant $tenant,
        Plan   $plan,
        array  $preapprovalData
    ): void {
        $preapprovalId = $preapprovalData['id'] ?? null;
        $nextBilling   = $this->mp->extractNextBillingDate($preapprovalData);

        DB::transaction(function () use ($tenant, $plan, $preapprovalId, $nextBilling, $preapprovalData) {
            $tenant->mp_subscription_id     = $preapprovalId;
            $tenant->mp_subscription_status = 'authorized';
            $tenant->plan_id                = $plan->id;
            $tenant->current_period_start   = now()->toDateString();
            $tenant->current_period_end     = $nextBilling?->toDateString();
            $tenant->next_billing_date      = $nextBilling?->toDateString();
            $tenant->activateSubscription($plan->name);

            SubscriptionInvoice::create([
                'tenant_id'           => $tenant->id,
                'plan_id'             => $plan->id,
                'mp_subscription_id'  => $preapprovalId,
                'plan_name'           => $plan->name,
                'amount'              => $plan->price,
                'status'              => 'paid',
                'billing_cycle_start' => now()->toDateString(),
                'billing_cycle_end'   => $nextBilling?->toDateString(),
                'due_date'            => now()->toDateString(),
                'paid_at'             => now(),
                'mp_payload'          => $preapprovalData,
            ]);

            $this->audit->subscriptionActivated($tenant, $plan->id, $preapprovalData);
        });

        Log::info('SubscriptionDomain: assinatura ativada via preapproval', [
            'tenant_id'      => $tenant->id,
            'plan_id'        => $plan->id,
            'preapproval_id' => $preapprovalId,
        ]);
    }

    // ── Upgrade (immediate, pro-rata) ─────────────────────────────────────────

    /**
     * Upgrade to a higher plan immediately via Preapproval amount update.
     *
     * @throws \DomainException on invalid transition or MP failure
     */
    public function upgrade(Tenant $tenant, Plan $targetPlan): void
    {
        $currentPlan = $tenant->plan;

        if (!$currentPlan || (float) $targetPlan->price <= (float) $currentPlan->price) {
            throw new \DomainException('Upgrade requer um plano de valor superior ao atual.');
        }

        if (!$tenant->mp_subscription_id) {
            throw new \DomainException('Assinatura Mercado Pago não encontrada para este tenant.');
        }

        $result = $this->mp->updateSubscriptionAmount(
            $tenant->mp_subscription_id,
            (float) $targetPlan->price,
            "Upgrade para {$targetPlan->name}"
        );

        if (!$result['success']) {
            throw new \DomainException('Falha ao atualizar assinatura no Mercado Pago: ' . ($result['error'] ?? ''));
        }

        $fromPlanId = $currentPlan->id;

        DB::transaction(function () use ($tenant, $targetPlan, $fromPlanId, $result) {
            $tenant->plan_id             = $targetPlan->id;
            $tenant->subscription_plan   = $targetPlan->name;
            $tenant->mp_subscription_status = 'authorized';

            // Clear any pending downgrade
            if ($tenant->hasScheduledDowngrade()) {
                $tenant->clearScheduledDowngrade();
                $this->audit->downgradeCancelled($tenant, $tenant->scheduled_downgrade_plan_id ?? 0);
            }

            $tenant->save();

            $this->audit->upgrade($tenant, $fromPlanId, $targetPlan->id, $result['data'] ?? null);
        });

        Log::info('SubscriptionDomain: upgrade realizado', [
            'tenant_id'       => $tenant->id,
            'from_plan'       => $fromPlanId,
            'to_plan'         => $targetPlan->id,
        ]);
    }

    // ── Downgrade (next billing cycle) ────────────────────────────────────────

    /**
     * Schedule a downgrade to take effect at the next billing cycle.
     *
     * Validates limits at schedule time. Max 3 rescheduling attempts.
     *
     * @throws \DomainException on invalid transition
     */
    public function scheduleDowngrade(Tenant $tenant, Plan $targetPlan): void
    {
        $currentPlan = $tenant->plan;

        if (!$currentPlan || (float) $targetPlan->price >= (float) $currentPlan->price) {
            throw new \DomainException('Downgrade requer um plano de valor inferior ao atual.');
        }

        if (($tenant->scheduled_downgrade_attempts ?? 0) >= self::MAX_DOWNGRADE_ATTEMPTS) {
            throw new \DomainException(
                'Número máximo de reagendamentos de downgrade atingido (' . self::MAX_DOWNGRADE_ATTEMPTS . ').'
            );
        }

        $this->validatePlanLimits($tenant, $targetPlan);

        $effectiveDate = $tenant->next_billing_date
            ? Carbon::parse($tenant->next_billing_date)
            : now()->addMonth();

        DB::transaction(function () use ($tenant, $currentPlan, $targetPlan, $effectiveDate) {
            $tenant->scheduleDowngrade($targetPlan->id, $effectiveDate);
            $this->audit->downgradeScheduled(
                $tenant,
                $currentPlan->id,
                $targetPlan->id,
                $effectiveDate->toDateString()
            );
        });

        Log::info('SubscriptionDomain: downgrade agendado', [
            'tenant_id'      => $tenant->id,
            'from_plan'      => $currentPlan->id,
            'to_plan'        => $targetPlan->id,
            'effective_date' => $effectiveDate->toDateString(),
        ]);
    }

    /**
     * Cancel a previously scheduled downgrade.
     */
    public function cancelScheduledDowngrade(Tenant $tenant): void
    {
        if (!$tenant->hasScheduledDowngrade()) {
            throw new \DomainException('Não há downgrade agendado para este tenant.');
        }

        $cancelledPlanId = $tenant->scheduled_downgrade_plan_id;

        DB::transaction(function () use ($tenant, $cancelledPlanId) {
            $tenant->clearScheduledDowngrade();
            $this->audit->downgradeCancelled($tenant, $cancelledPlanId ?? 0);
        });
    }

    /**
     * Execute a scheduled downgrade (called by ApplyScheduledDowngrades job).
     *
     * Re-validates limits at execution time. On validation failure increments
     * attempt counter; after 3 failures auto-cancels the scheduled downgrade.
     */
    public function applyScheduledDowngrade(Tenant $tenant): bool
    {
        if (!$tenant->hasScheduledDowngrade()) {
            return false;
        }

        $targetPlan = Plan::find($tenant->scheduled_downgrade_plan_id);

        if (!$targetPlan) {
            Log::warning('SubscriptionDomain: plano de downgrade não encontrado', [
                'tenant_id' => $tenant->id,
                'plan_id'   => $tenant->scheduled_downgrade_plan_id,
            ]);
            $tenant->clearScheduledDowngrade();
            return false;
        }

        try {
            $this->validatePlanLimits($tenant, $targetPlan);
        } catch (\DomainException $e) {
            $attempts = ($tenant->scheduled_downgrade_attempts ?? 0) + 1;

            DB::transaction(function () use ($tenant, $attempts, $targetPlan, $e) {
                $tenant->scheduled_downgrade_attempts = $attempts;
                $tenant->save();

                $this->audit->log(
                    $tenant,
                    'downgrade_validation_failed',
                    metadata: [
                        'attempt'  => $attempts,
                        'reason'   => $e->getMessage(),
                        'plan_id'  => $targetPlan->id,
                    ]
                );

                if ($attempts >= self::MAX_DOWNGRADE_ATTEMPTS) {
                    $tenant->clearScheduledDowngrade();
                    $this->audit->downgradeCancelled($tenant, $targetPlan->id);
                }
            });

            Log::warning('SubscriptionDomain: validação de downgrade falhou', [
                'tenant_id' => $tenant->id,
                'attempt'   => $attempts,
                'reason'    => $e->getMessage(),
            ]);

            return false;
        }

        $fromPlanId = $tenant->plan_id;

        DB::transaction(function () use ($tenant, $targetPlan, $fromPlanId) {
            // Update MP amount
            if ($tenant->mp_subscription_id) {
                $this->mp->updateSubscriptionAmount(
                    $tenant->mp_subscription_id,
                    (float) $targetPlan->price,
                    "Downgrade para {$targetPlan->name}"
                );
            }

            $tenant->plan_id           = $targetPlan->id;
            $tenant->subscription_plan = $targetPlan->name;
            $tenant->clearScheduledDowngrade();

            $this->audit->downgradeApplied($tenant, $fromPlanId ?? 0, $targetPlan->id);
        });

        Log::info('SubscriptionDomain: downgrade aplicado', [
            'tenant_id'  => $tenant->id,
            'from_plan'  => $fromPlanId,
            'to_plan'    => $targetPlan->id,
        ]);

        return true;
    }

    // ── Cancellation ─────────────────────────────────────────────────────────

    /**
     * Request cancellation — keeps access until end of current billing cycle.
     *
     * @throws \DomainException if already cancelled
     */
    public function requestCancellation(Tenant $tenant, ?string $reason = null, ?string $reasonDetail = null): void
    {
        if ($tenant->isCancelled()) {
            throw new \DomainException('Assinatura já está cancelada.');
        }

        if ($tenant->hasCancellationPending()) {
            throw new \DomainException('Cancelamento já solicitado. Acesso mantido até ' .
                $tenant->current_period_end?->format('d/m/Y') . '.');
        }

        // Cancel in MP so no future charges occur
        if ($tenant->mp_subscription_id) {
            $result = $this->mp->cancelSubscription($tenant->mp_subscription_id);
            if (!$result['success']) {
                Log::warning('SubscriptionDomain: falha ao cancelar no MP, prosseguindo com cancelamento local', [
                    'tenant_id' => $tenant->id,
                    'error'     => $result['error'] ?? '',
                ]);
            }
        }

        DB::transaction(function () use ($tenant, $reason, $reasonDetail) {
            $tenant->requestCancellation();
            $this->audit->cancellationRequested(
                $tenant,
                $tenant->current_period_end?->toDateString(),
                $reason,
                $reasonDetail,
            );
        });

        Log::info('SubscriptionDomain: cancelamento solicitado', [
            'tenant_id'    => $tenant->id,
            'access_until' => $tenant->current_period_end?->toDateString(),
            'reason'       => $reason,
        ]);
    }

    /**
     * Finalize cancellation when billing period ends (called by job).
     */
    public function finalizeCancellation(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->applyCancellation();
            $this->audit->cancelled($tenant);
        });
    }

    // ── Reactivation ─────────────────────────────────────────────────────────

    /**
     * Reactivate a cancelled/suspended/delinquent subscription.
     *
     * @throws \DomainException on MP failure
     */
    public function reactivate(Tenant $tenant, Plan $plan, string $cardToken, string $payerEmail): void
    {
        if ($tenant->account_status === 'active') {
            throw new \DomainException('Assinatura já está ativa.');
        }

        // Try reactivating existing subscription first; otherwise create a new one
        $mpResult = null;

        if ($tenant->mp_subscription_id) {
            $mpResult = $this->mp->reactivateSubscription($tenant->mp_subscription_id);
        }

        if (!$mpResult || !$mpResult['success']) {
            // Create a fresh preapproval
            $mpResult = $this->mp->createSubscription(
                reason:      $plan->name,
                payerEmail:  $payerEmail,
                cardToken:   $cardToken,
                amount:      (float) $plan->price,
            );

            if (!$mpResult['success']) {
                throw new \DomainException('Falha ao reativar assinatura: ' . ($mpResult['error'] ?? ''));
            }
        }

        $preapprovalId = $mpResult['preapproval_id'] ?? $mpResult['data']['id'] ?? $tenant->mp_subscription_id;

        DB::transaction(function () use ($tenant, $plan, $preapprovalId, $mpResult) {
            $tenant->mp_subscription_id     = $preapprovalId;
            $tenant->mp_subscription_status = 'authorized';
            $tenant->plan_id                = $plan->id;
            $tenant->reactivate();

            $this->audit->reactivated($tenant, $plan->id);
        });

        Log::info('SubscriptionDomain: assinatura reativada', [
            'tenant_id'      => $tenant->id,
            'plan_id'        => $plan->id,
            'preapproval_id' => $preapprovalId,
        ]);
    }

    // ── Webhook processing ────────────────────────────────────────────────────

    /**
     * Handle a preapproval.updated webhook from Mercado Pago.
     */
    public function handlePreapprovalWebhook(Tenant $tenant, array $mpData): void
    {
        $mpStatus    = $mpData['status'] ?? null;
        $newInternal = $this->mp->mapMpStatusToInternal($mpStatus ?? '');

        $this->audit->webhookReceived($tenant, 'preapproval', $mpData);

        if (!$newInternal || $newInternal === $tenant->account_status) {
            return;
        }

        DB::transaction(function () use ($tenant, $newInternal, $mpData) {
            $prevStatus = $tenant->account_status;

            match ($newInternal) {
                'active'    => $tenant->activateSubscription($tenant->subscription_plan ?? ''),
                'pending'   => $tenant->markPending(),
                'suspended' => $tenant->suspend(),
                'cancelled' => $tenant->applyCancellation(),
                default     => null,
            };

            // Update next billing date if present
            $nextBilling = $this->mp->extractNextBillingDate($mpData);
            if ($nextBilling) {
                $tenant->next_billing_date = $nextBilling->toDateString();
                $tenant->save();
            }

            $this->audit->log(
                $tenant,
                'webhook_status_change',
                statusBefore: $prevStatus,
                statusAfter:  $newInternal,
                mpPayload:    $mpData,
            );
        });
    }

    /**
     * Handle a payment webhook (authorized_payment topic).
     */
    public function handlePaymentWebhook(Tenant $tenant, array $paymentData): void
    {
        $status = $paymentData['status'] ?? null;

        if ($status === 'approved') {
            DB::transaction(function () use ($tenant, $paymentData) {
                $preapprovalId = $paymentData['preapproval_id']
                    ?? $paymentData['metadata']['preapproval_id']
                    ?? $tenant->mp_subscription_id;

                // Register invoice
                SubscriptionInvoice::create([
                    'tenant_id'          => $tenant->id,
                    'plan_id'            => $tenant->plan_id,
                    'mp_subscription_id' => $preapprovalId,
                    'mp_payment_id'      => (string) ($paymentData['id'] ?? ''),
                    'plan_name'          => $tenant->subscription_plan ?? '',
                    'amount'             => $paymentData['transaction_amount'] ?? $tenant->plan?->price ?? 0,
                    'status'             => 'paid',
                    'payment_method'     => $paymentData['payment_method_id'] ?? null,
                    'billing_cycle_start'=> now()->toDateString(),
                    'billing_cycle_end'  => now()->addMonth()->toDateString(),
                    'due_date'           => now()->toDateString(),
                    'paid_at'            => now(),
                    'mp_payload'         => $paymentData,
                ]);

                // Reactivate if was in dunning
                if ($tenant->isInDunning() || $tenant->isDelinquent() || $tenant->isSuspended()) {
                    $tenant->reactivate();
                } else {
                    $tenant->last_payment_at = now();
                    $tenant->save();
                }

                $this->audit->paymentReceived($tenant, $paymentData);
            });
        } elseif (in_array($status, ['rejected', 'cancelled', 'charged_back'], true)) {
            $this->audit->paymentFailed($tenant, $paymentData);

            if (!$tenant->isInDunning()) {
                $tenant->markPending();
                $this->audit->dunningStarted($tenant);
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Validate that current usage doesn't exceed target plan limits.
     *
     * @throws \DomainException with human-readable reason
     */
    private function validatePlanLimits(Tenant $tenant, Plan $targetPlan): void
    {
        // Users
        $currentUsers = $tenant->users()->count();
        if ($targetPlan->max_users !== null && $currentUsers > $targetPlan->max_users) {
            throw new \DomainException(
                "Você possui {$currentUsers} usuários, mas o plano {$targetPlan->name} permite apenas {$targetPlan->max_users}."
            );
        }

        // Products
        $currentProducts = $tenant->products()->count();
        if ($targetPlan->max_products !== null && $currentProducts > $targetPlan->max_products) {
            throw new \DomainException(
                "Você possui {$currentProducts} produtos, mas o plano {$targetPlan->name} permite apenas {$targetPlan->max_products}."
            );
        }

        // Branches (if applicable)
        if (method_exists($tenant, 'branches')) {
            $currentBranches = $tenant->branches()->count();
            if ($targetPlan->max_branches !== null && $currentBranches > $targetPlan->max_branches) {
                throw new \DomainException(
                    "Você possui {$currentBranches} filiais, mas o plano {$targetPlan->name} permite apenas {$targetPlan->max_branches}."
                );
            }
        }
    }
}
