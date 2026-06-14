<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAccountPayableRequest;
use App\Http\Requests\Api\UpdateAccountPayableRequest;
use App\Http\Resources\AccountPayableResource;
use App\Services\AccountPayableService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountPayableApiController extends Controller
{
    public function __construct(
        private readonly AccountPayableService $accountPayableService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $filters = [
                'status' => $request->query('status'),
                'supplier_id' => $request->query('supplier_id'),
                'category_id' => $request->query('category_id'),
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'overdue' => $request->query('overdue') === 'true',
            ];

            $accountsPayable = $this->accountPayableService->getAllAccountsPayable($tenantId, $filters);

            return ApiResponseClass::sendResponse(
                AccountPayableResource::collection($accountsPayable),
                'Contas a pagar recuperadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar contas a pagar');
        }
    }

    public function store(StoreAccountPayableRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $data = array_merge($request->validated(), ['tenant_id' => $tenantId]);
            $accountPayable = $this->accountPayableService->createAccountPayable($data);

            return ApiResponseClass::sendResponse(
                new AccountPayableResource($accountPayable),
                'Conta a pagar criada com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar conta a pagar');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $accountPayable = $this->accountPayableService->getAccountPayableByUuid($uuid);

            if (!$accountPayable || $accountPayable->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Conta a pagar não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new AccountPayableResource($accountPayable),
                'Conta a pagar recuperada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar conta a pagar');
        }
    }

    public function update(UpdateAccountPayableRequest $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $accountPayable = $this->accountPayableService->getAccountPayableByUuid($uuid);

            if (!$accountPayable || $accountPayable->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Conta a pagar não encontrada', 404);
            }

            $updatedAccountPayable = $this->accountPayableService->updateAccountPayable(
                $accountPayable->id,
                $request->validated()
            );

            return ApiResponseClass::sendResponse(
                new AccountPayableResource($updatedAccountPayable),
                'Conta a pagar atualizada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar conta a pagar');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $accountPayable = $this->accountPayableService->getAccountPayableByUuid($uuid);

            if (!$accountPayable || $accountPayable->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Conta a pagar não encontrada', 404);
            }

            $this->accountPayableService->deleteAccountPayable($accountPayable->id);

            return ApiResponseClass::sendResponse(null, 'Conta a pagar excluída com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir conta a pagar');
        }
    }

    public function pay(Request $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $accountPayable = $this->accountPayableService->getAccountPayableByUuid($uuid);

            if (!$accountPayable || $accountPayable->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Conta a pagar não encontrada', 404);
            }

            $paymentData = [
                'payment_date' => $request->input('payment_date', now()->format('Y-m-d')),
                'amount_paid' => $request->input('amount_paid'),
                'payment_method_id' => $request->input('payment_method_id'),
                'notes' => $request->input('notes'),
            ];

            $updatedAccountPayable = $this->accountPayableService->markAsPaid(
                $accountPayable->id,
                $paymentData
            );

            return ApiResponseClass::sendResponse(
                new AccountPayableResource($updatedAccountPayable),
                'Pagamento registrado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao registrar pagamento');
        }
    }

    public function stats(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $stats = $this->accountPayableService->getStats($tenantId, $request->query());

            return ApiResponseClass::sendResponse($stats, 'Estatísticas recuperadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar estatísticas');
        }
    }

    public function alerts(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $days = $request->query('days', 7); // Alertas dos próximos 7 dias por padrão
            $alerts = $this->accountPayableService->getDueAlerts($tenantId, $days);

            return ApiResponseClass::sendResponse(
                AccountPayableResource::collection($alerts),
                'Alertas de vencimento recuperados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar alertas');
        }
    }
}

