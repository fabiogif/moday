<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\ReorderCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReorderSuggestionController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly ReorderCalculatorService $reorderService,
    ) {}

    /** GET /api/reorder/suggestions */
    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $suggestions = $this->reorderService->listPending($tenantId);
            return ApiResponseClass::sendResponse($suggestions, 'Sugestões de reposição', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar sugestões');
        }
    }

    /** POST /api/reorder/suggestions/generate */
    public function generate(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $created = $this->reorderService->generateForTenant($tenantId);

            return ApiResponseClass::sendResponse(
                ['generated' => $created],
                "{$created} sugestões de reposição geradas com sucesso",
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar sugestões');
        }
    }

    /** POST /api/reorder/suggestions/{id}/create-purchase-order */
    public function createPurchaseOrder(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $po = $this->reorderService->createPurchaseOrder($tenantId, $id);
            return ApiResponseClass::sendResponse($po, 'Pedido de compra criado com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar pedido de compra');
        }
    }

    /** PATCH /api/reorder/suggestions/{id}/dismiss */
    public function dismiss(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $this->reorderService->dismiss($tenantId, $id);
            return ApiResponseClass::sendResponse(null, 'Sugestão descartada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao descartar sugestão');
        }
    }
}
