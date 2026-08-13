<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantBilling;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\Log;

readonly class SubscriptionService
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepository,
        private PlanRepositoryInterface $planRepository,
        private MercadoPagoService $mercadoPagoService,
    ) {}

    public function getTrialStatus(int $tenantId): ?array
    {
        $tenant = $this->tenantRepository->findById($tenantId);

        return $tenant?->toTrialStatusArray();
    }

    public function getAvailablePlans(): mixed
    {
        return $this->planRepository->getActivePlansWithDetails();
    }

    public function activateSubscription(
        User $user,
        int $tenantId,
        int $planId,
        string $paymentMethod = '',
        ?array $paymentData = null
    ): array {
        $tenant = $this->tenantRepository->findById($tenantId);
        $plan = $this->planRepository->getById($planId);

        if (!$tenant || !$plan) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        Log::info('Ativando assinatura', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'payment_method' => $paymentMethod,
            'payment_data' => $paymentData,
        ]);

        $tenant->plan_id = $plan->id;

        if ($plan->isFree()) {
            $tenant->activateFreePlan($plan->name);
            $tenant->mrr = 0;
            $tenant->save();
        } else {
            $tenant->activateSubscription($plan->name);
            $tenant->save();
        }

        Log::info('Assinatura ativada com sucesso', [
            'tenant_id' => $tenant->id,
            'plan' => $plan->name,
        ]);

        return [
            'success' => true,
            'tenant' => $tenant,
            'plan' => $plan,
            'message' => 'Assinatura ativada com sucesso! Bem-vindo ao DistribTec.',
        ];
    }

    /**
     * Ativa plano gratuito sem cobrança (trial expirado / migração para Grátis).
     * Recusa planos pagos — esses exigem /subscription/payment.
     */
    public function activateFreePlan(User $user, int $tenantId, int $planId): array
    {
        $tenant = $this->tenantRepository->findById($tenantId);
        $plan = $this->planRepository->getById($planId);

        if (!$tenant || !$plan) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        if (!$plan->isFree()) {
            return ['success' => false, 'reason' => 'payment_required'];
        }

        if (!$plan->is_active) {
            return ['success' => false, 'reason' => 'plan_inactive'];
        }

        Log::info('Ativando plano gratuito', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
        ]);

        $tenant->activateFreePlan($plan->name);
        $tenant->plan_id = $plan->id;
        $tenant->mrr = 0;
        $tenant->save();

        return [
            'success' => true,
            'tenant' => $tenant->fresh(),
            'plan' => $plan,
            'message' => 'Plano gratuito ativado com sucesso!',
        ];
    }

    public function processPayment(
        User   $user,
        int    $tenantId,
        int    $planId,
        string $token,
        string $paymentMethodId,
        string $paymentTypeId,
        int    $installments,
        string $payerEmail
    ): array {
        $plan = $this->planRepository->getById($planId);

        if (!$plan) {
            return ['success' => false, 'reason' => 'plan_not_found'];
        }

        // Plano gratuito não passa pelo Mercado Pago (amount=0 é inválido no Brick/API).
        if ($plan->isFree()) {
            return $this->activateFreePlan($user, $tenantId, $planId);
        }

        Log::info('Processando pagamento MP', [
            'user_id'    => $user->id,
            'tenant_id'  => $tenantId,
            'plan_id'    => $plan->id,
            'amount'     => $plan->price,
            'method'     => $paymentMethodId,
        ]);

        $externalRef    = "tenant_{$tenantId}_plan_{$planId}_" . time();
        $paymentResult  = $this->mercadoPagoService->createCardPayment(
            amount:            (float) $plan->price,
            token:             $token,
            paymentMethodId:   $paymentMethodId,
            paymentTypeId:     $paymentTypeId,
            installments:      $installments,
            payerEmail:        $payerEmail,
            externalReference: $externalRef,
        );

        if (!$paymentResult['success']) {
            return [
                'success' => false,
                'reason'  => 'payment_rejected',
                'error'   => $paymentResult['error'] ?? 'Pagamento recusado',
                'payment' => $paymentResult,
            ];
        }

        $result = $this->activateSubscription($user, $tenantId, $planId, $paymentMethodId, $paymentResult);

        // Registrar billing
        if ($result['success']) {
            try {
                TenantBilling::createForTenant(
                    $result['tenant'],
                    (float) $plan->price,
                    $plan->name
                )->markAsPaid($paymentMethodId);
            } catch (\Throwable $e) {
                Log::warning('Falha ao registrar billing: ' . $e->getMessage());
            }
        }

        return $result;
    }

    public function cancelSubscription(int $tenantId): ?Tenant
    {
        $tenant = $this->tenantRepository->findById($tenantId);
        if (!$tenant) {
            return null;
        }

        $tenant->suspend();

        Log::info('Assinatura cancelada', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
        ]);

        return $tenant;
    }

    public function reactivateSubscription(int $tenantId): array
    {
        $tenant = $this->tenantRepository->findById($tenantId);

        if (!$tenant) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        if ($tenant->account_status !== 'suspended') {
            return ['success' => false, 'reason' => 'not_suspended'];
        }

        $tenant->reactivate();

        Log::info('Assinatura reativada', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
        ]);

        return ['success' => true, 'tenant' => $tenant];
    }
}
