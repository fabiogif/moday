<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCancellationRequested;
use App\Events\SubscriptionReactivated;
use App\Events\SubscriptionUpgraded;
use App\Events\SubscriptionDowngradeScheduled;
use App\Http\Requests\Api\ActivateSubscriptionRequest;
use App\Http\Requests\Api\ProcessSubscriptionPaymentRequest;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\MercadoPagoService;
use App\Services\SubscriptionDomainService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionApiController extends Controller
{
    public function __construct(
        private readonly SubscriptionService       $subscriptionService,
        private readonly MercadoPagoService        $mercadoPagoService,
        private readonly SubscriptionDomainService $domain,
    ) {}

    // ── Existing endpoints (legacy / hybrid mode) ─────────────────────────────

    public function trialStatus(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }

            $status = $this->subscriptionService->getTrialStatus($user->tenant_id);

            if (!$status) {
                return ApiResponseClass::sendResponse(null, 'Tenant não encontrado', 404);
            }

            return ApiResponseClass::sendResponse($status, 'Status do trial recuperado com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao buscar status do trial: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao buscar status do trial');
        }
    }

    public function plans(): JsonResponse
    {
        try {
            $plans = $this->subscriptionService->getAvailablePlans();

            return ApiResponseClass::sendResponse($plans, 'Planos recuperados com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao buscar planos: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao buscar planos');
        }
    }

    public function activate(ActivateSubscriptionRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = Auth::user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }

            // Somente plano gratuito — planos pagos exigem /subscription/payment.
            $result = $this->subscriptionService->activateFreePlan(
                $user,
                $user->tenant_id,
                (int) $data['plan_id'],
            );

            if (($result['reason'] ?? '') === 'payment_required') {
                return ApiResponseClass::sendResponse(
                    null,
                    'Planos pagos exigem pagamento. Use o fluxo de assinatura.',
                    422
                );
            }

            if (($result['reason'] ?? '') === 'plan_inactive') {
                return ApiResponseClass::sendResponse(null, 'O plano selecionado não está ativo.', 422);
            }

            if (!($result['success'] ?? false)) {
                return ApiResponseClass::sendResponse(null, 'Plano ou tenant não encontrado', 404);
            }

            return ApiResponseClass::sendResponse([
                'tenant'  => $result['tenant'],
                'plan'    => $result['plan'],
                'message' => $result['message'],
            ], 'Assinatura ativada com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao ativar assinatura: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao ativar assinatura');
        }
    }

    public function payment(ProcessSubscriptionPaymentRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = Auth::user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }

            $billingMode = config('services.mercadopago.billing_mode', 'legacy');

            if ($billingMode === 'preapproval') {
                return $this->handlePreapprovalPayment($data, $user);
            }

            $result = $this->subscriptionService->processPayment(
                user:            $user,
                tenantId:        $user->tenant_id,
                planId:          $data['plan_id'],
                token:           $data['token'],
                paymentMethodId: $data['payment_method_id'],
                paymentTypeId:   $data['payment_type_id'],
                installments:    $data['installments'],
                payerEmail:      $data['payer_email'],
            );

            if (($result['reason'] ?? '') === 'plan_not_found') {
                return ApiResponseClass::sendResponse(null, 'Plano não encontrado', 404);
            }

            if (!($result['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Pagamento não aprovado',
                    'data'    => $result['payment'] ?? null,
                ], 422);
            }

            return ApiResponseClass::sendResponse([
                'tenant'  => $result['tenant'],
                'plan'    => $result['plan'],
                'message' => $result['message'],
            ], 'Assinatura ativada com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao processar pagamento: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao processar pagamento');
        }
    }

    private function handlePreapprovalPayment(array $data, $user): JsonResponse
    {
        $tenant = Tenant::find($user->tenant_id);
        $plan   = Plan::find($data['plan_id']);

        if (!$tenant || !$plan) {
            return ApiResponseClass::sendResponse(null, 'Tenant ou plano não encontrado', 404);
        }

        if ($plan->isFree()) {
            $result = $this->subscriptionService->activateFreePlan($user, (int) $user->tenant_id, (int) $plan->id);
            if (!($result['success'] ?? false)) {
                return ApiResponseClass::sendResponse(null, $result['message'] ?? 'Falha ao ativar plano gratuito', 422);
            }

            return ApiResponseClass::sendResponse([
                'tenant'  => $result['tenant'],
                'plan'    => $result['plan'],
                'message' => $result['message'],
            ], 'Assinatura ativada com sucesso');
        }

        $result = $this->mercadoPagoService->createSubscription(
            reason:    $plan->name,
            payerEmail: $data['payer_email'],
            cardToken:  $data['token'],
            amount:     (float) $plan->price,
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Falha ao criar assinatura recorrente',
            ], 422);
        }

        $this->domain->activateFromPreapproval($tenant, $plan, $result['data'] ?? []);

        event(new SubscriptionActivated($tenant->fresh(), $plan));

        return ApiResponseClass::sendResponse([
            'tenant'         => $tenant->fresh()->toTrialStatusArray(),
            'plan'           => $plan,
            'preapproval_id' => $result['preapproval_id'],
            'message'        => 'Assinatura recorrente ativada com sucesso!',
        ], 'Assinatura ativada com sucesso');
    }

    /**
     * Legacy webhook (Orders API v1 — kept for hybrid mode).
     */
    public function webhook(Request $request): JsonResponse
    {
        $topic = $request->query('topic') ?? $request->input('type', '');
        $id    = $request->query('id')    ?? $request->input('data.id', '');

        Log::info('MP Webhook recebido', ['topic' => $topic, 'id' => $id]);

        if (!in_array($topic, ['order', 'orders', 'payment'], true) || !$id) {
            return response()->json(['ok' => true]);
        }

        try {
            $orderData = $this->mercadoPagoService->getOrder((string) $id);

            if (!($orderData['success'] ?? false)) {
                return response()->json(['error' => 'order not found'], 404);
            }

            $order       = $orderData['data'];
            $externalRef = $order['external_reference'] ?? '';

            if (!preg_match('/^tenant_(\d+)_plan_(\d+)_/', $externalRef, $matches)) {
                return response()->json(['error' => 'invalid reference'], 422);
            }

            $tenantId = (int) $matches[1];
            $planId   = (int) $matches[2];
            $tenant   = Tenant::find($tenantId);

            if ($tenant?->account_status === 'active' && (int) $tenant->plan_id === $planId) {
                return response()->json(['ok' => true, 'status' => 'already_active']);
            }

            $paymentStatus = $order['transactions']['payments'][0]['status'] ?? null;

            if (in_array($paymentStatus, ['processed', 'approved'], true)) {
                $plan = Plan::find($planId);

                if ($tenant && $plan) {
                    $tenant->activateSubscription($plan->name);
                    $tenant->plan_id = $plan->id;
                    $tenant->save();
                }
            }
        } catch (\Throwable $e) {
            Log::error('MP Webhook: erro ao processar', ['error' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }

    public function cancel(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }

            $tenant = Tenant::find($user->tenant_id);

            if (!$tenant) {
                return ApiResponseClass::sendResponse(null, 'Tenant não encontrado', 404);
            }

            $billingMode = config('services.mercadopago.billing_mode', 'legacy');

            if ($billingMode === 'preapproval') {
                $this->domain->requestCancellation($tenant);
                event(new SubscriptionCancellationRequested(
                    $tenant->fresh(),
                    $tenant->current_period_end?->toDateString()
                ));
                return ApiResponseClass::sendResponse([
                    'access_until' => $tenant->current_period_end?->format('d/m/Y'),
                    'message'      => 'Cancelamento solicitado. Acesso mantido até o fim do ciclo.',
                ], 'Cancelamento solicitado com sucesso');
            }

            $cancelledTenant = $this->subscriptionService->cancelSubscription($user->tenant_id);

            if (!$cancelledTenant) {
                return ApiResponseClass::sendResponse(null, 'Tenant não encontrado', 404);
            }

            return ApiResponseClass::sendResponse([
                'tenant'  => $cancelledTenant,
                'message' => 'Assinatura cancelada. Seus dados foram preservados.',
            ], 'Assinatura cancelada com sucesso');
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar assinatura: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao cancelar assinatura');
        }
    }

    public function reactivate(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }

            $billingMode = config('services.mercadopago.billing_mode', 'legacy');

            if ($billingMode === 'preapproval') {
                $request->validate([
                    'card_token'  => 'required|string',
                    'payer_email' => 'required|email',
                    'plan_id'     => 'sometimes|integer|exists:plans,id',
                ]);

                $tenant = Tenant::find($user->tenant_id);
                $plan   = $tenant?->plan ?? Plan::find($request->input('plan_id'));

                if (!$tenant || !$plan) {
                    return ApiResponseClass::sendResponse(null, 'Tenant ou plano não encontrado', 404);
                }

                $this->domain->reactivate($tenant, $plan, $request->input('card_token'), $request->input('payer_email'));

                event(new SubscriptionReactivated($tenant->fresh(), $plan));

                return ApiResponseClass::sendResponse([
                    'tenant'  => $tenant->fresh()->toTrialStatusArray(),
                    'message' => 'Assinatura reativada com sucesso!',
                ], 'Assinatura reativada com sucesso');
            }

            $result = $this->subscriptionService->reactivateSubscription($user->tenant_id);

            if (($result['reason'] ?? '') === 'not_found') {
                return ApiResponseClass::sendResponse(null, 'Tenant não encontrado', 404);
            }

            if (($result['reason'] ?? '') === 'not_suspended') {
                return ApiResponseClass::sendResponse(null, 'Conta não está suspensa', 422);
            }

            return ApiResponseClass::sendResponse([
                'tenant'  => $result['tenant'],
                'message' => 'Assinatura reativada com sucesso!',
            ], 'Assinatura reativada com sucesso');
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao reativar assinatura: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao reativar assinatura');
        }
    }

    // ── New v2 endpoints (Preapproval mode) ───────────────────────────────────

    public function upgrade(Request $request): JsonResponse
    {
        $request->validate(['plan_id' => 'required|integer|exists:plans,id']);

        try {
            $user   = Auth::user();
            $tenant = Tenant::find($user?->tenant_id);
            $plan   = Plan::find($request->input('plan_id'));

            if (!$tenant || !$plan) {
                return ApiResponseClass::sendResponse(null, 'Tenant ou plano não encontrado', 404);
            }

            $fromPlan = $tenant->plan;
            $this->domain->upgrade($tenant, $plan);

            event(new SubscriptionUpgraded($tenant->fresh(), $fromPlan, $plan));

            return ApiResponseClass::sendResponse([
                'tenant'  => $tenant->fresh()->toTrialStatusArray(),
                'message' => "Upgrade para o plano {$plan->name} realizado com sucesso!",
            ], 'Upgrade realizado com sucesso');
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao realizar upgrade: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao realizar upgrade');
        }
    }

    public function scheduleDowngrade(Request $request): JsonResponse
    {
        $request->validate(['plan_id' => 'required|integer|exists:plans,id']);

        try {
            $user   = Auth::user();
            $tenant = Tenant::find($user?->tenant_id);
            $plan   = Plan::find($request->input('plan_id'));

            if (!$tenant || !$plan) {
                return ApiResponseClass::sendResponse(null, 'Tenant ou plano não encontrado', 404);
            }

            $fromPlan = $tenant->plan;
            $this->domain->scheduleDowngrade($tenant, $plan);

            $effectiveDate = Carbon::parse($tenant->fresh()->scheduled_downgrade_at);
            event(new SubscriptionDowngradeScheduled($tenant->fresh(), $fromPlan, $plan, $effectiveDate));

            return ApiResponseClass::sendResponse([
                'tenant'         => $tenant->fresh()->toTrialStatusArray(),
                'effective_date' => $effectiveDate->format('d/m/Y'),
                'message'        => "Downgrade para {$plan->name} agendado para {$effectiveDate->format('d/m/Y')}.",
            ], 'Downgrade agendado com sucesso');
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao agendar downgrade: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao agendar downgrade');
        }
    }

    public function cancelDowngrade(Request $request): JsonResponse
    {
        try {
            $user   = Auth::user();
            $tenant = Tenant::find($user?->tenant_id);

            if (!$tenant) {
                return ApiResponseClass::sendResponse(null, 'Tenant não encontrado', 404);
            }

            $this->domain->cancelScheduledDowngrade($tenant);

            return ApiResponseClass::sendResponse([
                'tenant'  => $tenant->fresh()->toTrialStatusArray(),
                'message' => 'Downgrade cancelado. Você continuará no plano atual.',
            ], 'Downgrade cancelado com sucesso');
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar downgrade: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao cancelar downgrade');
        }
    }

    public function updateCard(Request $request): JsonResponse
    {
        $request->validate(['card_token' => 'required|string']);

        try {
            $user   = Auth::user();
            $tenant = Tenant::find($user?->tenant_id);

            if (!$tenant) {
                return ApiResponseClass::sendResponse(null, 'Tenant não encontrado', 404);
            }

            if (!$tenant->mp_subscription_id) {
                return response()->json(['success' => false, 'message' => 'Nenhuma assinatura recorrente encontrada.'], 422);
            }

            $result = $this->mercadoPagoService->updateCard(
                $tenant->mp_subscription_id,
                $request->input('card_token')
            );

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => $result['error'] ?? 'Falha ao atualizar cartão.'], 422);
            }

            return ApiResponseClass::sendResponse(null, 'Cartão atualizado com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar cartão: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao atualizar cartão');
        }
    }

    public function invoices(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }

            $invoices = \App\Models\SubscriptionInvoice::forTenant($user->tenant_id)
                ->orderByDesc('created_at')
                ->paginate(12);

            return ApiResponseClass::sendResponse($invoices, 'Faturas recuperadas com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao buscar faturas: ' . $e->getMessage());
            return ApiResponseClass::rollback($e, 'Erro ao buscar faturas');
        }
    }
}
