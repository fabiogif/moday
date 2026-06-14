<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAccountReceivableRequest;
use App\Http\Requests\Api\UpdateAccountReceivableRequest;
use App\Http\Resources\AccountReceivableResource;
use App\Services\AccountReceivableService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountReceivableApiController extends Controller
{
    public function __construct(
        private readonly AccountReceivableService $accountReceivableService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $filters = [
                'status' => $request->query('status'),
                'client_id' => $request->query('client_id'),
                'category_id' => $request->query('category_id'),
                'order_id' => $request->query('order_id'),
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'overdue' => $request->query('overdue') === 'true',
            ];

            $accountsReceivable = $this->accountReceivableService->getAllAccountsReceivable($tenantId, $filters);

            return ApiResponseClass::sendResponse(
                AccountReceivableResource::collection($accountsReceivable),
                'Contas a receber recuperadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar contas a receber');
        }
    }

    public function store(StoreAccountReceivableRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $data = array_merge($request->validated(), ['tenant_id' => $tenantId]);
            $accountReceivable = $this->accountReceivableService->createAccountReceivable($data);

            return ApiResponseClass::sendResponse(
                new AccountReceivableResource($accountReceivable),
                'Conta a receber criada com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar conta a receber');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $accountReceivable = $this->accountReceivableService->getAccountReceivableByUuid($uuid);

            if (!$accountReceivable || $accountReceivable->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Conta a receber não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new AccountReceivableResource($accountReceivable),
                'Conta a receber recuperada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar conta a receber');
        }
    }

    public function update(UpdateAccountReceivableRequest $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $accountReceivable = $this->accountReceivableService->getAccountReceivableByUuid($uuid);

            if (!$accountReceivable || $accountReceivable->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Conta a receber não encontrada', 404);
            }

            $updatedAccountReceivable = $this->accountReceivableService->updateAccountReceivable(
                $accountReceivable->id,
                $request->validated()
            );

            return ApiResponseClass::sendResponse(
                new AccountReceivableResource($updatedAccountReceivable),
                'Conta a receber atualizada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar conta a receber');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $accountReceivable = $this->accountReceivableService->getAccountReceivableByUuid($uuid);

            if (!$accountReceivable || $accountReceivable->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Conta a receber não encontrada', 404);
            }

            $this->accountReceivableService->deleteAccountReceivable($accountReceivable->id);

            return ApiResponseClass::sendResponse(null, 'Conta a receber excluída com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir conta a receber');
        }
    }

    public function receive(Request $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $accountReceivable = $this->accountReceivableService->getAccountReceivableByUuid($uuid);

            if (!$accountReceivable || $accountReceivable->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Conta a receber não encontrada', 404);
            }

            $receiptData = [
                'receipt_date' => $request->input('receipt_date', now()->format('Y-m-d')),
                'amount_received' => $request->input('amount_received'),
                'payment_method_id' => $request->input('payment_method_id'),
                'notes' => $request->input('notes'),
            ];

            $updatedAccountReceivable = $this->accountReceivableService->markAsReceived(
                $accountReceivable->id,
                $receiptData
            );

            return ApiResponseClass::sendResponse(
                new AccountReceivableResource($updatedAccountReceivable),
                'Recebimento registrado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao registrar recebimento');
        }
    }

    public function stats(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $stats = $this->accountReceivableService->getStats($tenantId, $request->query());

            return ApiResponseClass::sendResponse($stats, 'Estatísticas recuperadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar estatísticas');
        }
    }

    public function fromOrder(int $orderId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $accountsReceivable = $this->accountReceivableService->getByOrderId($orderId, $tenantId);

            return ApiResponseClass::sendResponse(
                AccountReceivableResource::collection($accountsReceivable),
                'Contas a receber do pedido recuperadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar contas do pedido');
        }
    }
}

