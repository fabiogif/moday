<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\Stock\BatchQueryService;
use App\Services\Stock\BatchRegulatoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly BatchQueryService $batchQueryService,
        private readonly BatchRegulatoryService $batchRegulatoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $perPage = min((int) $request->get('per_page', 50), 100);
            $filters = $request->only(['product_id', 'warehouse_id', 'status']);

            $paginated = $this->batchQueryService->list($tenantId, $filters, $perPage);

            return response()->json([
                'success' => true,
                'data'    => $paginated->items(),
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
                'message' => 'Lotes recuperados com sucesso',
            ], 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar lotes');
        }
    }

    public function expiring(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $days    = max(1, (int) $request->get('days', 60));
            $batches = $this->batchQueryService->expiring($tenantId, $days);

            return ApiResponseClass::sendResponse($batches, 'Lotes a vencer recuperados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar lotes a vencer');
        }
    }

    public function expired(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $batches = $this->batchQueryService->expired($tenantId);

            return ApiResponseClass::sendResponse($batches, 'Lotes vencidos recuperados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar lotes vencidos');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $batch = $this->batchQueryService->find($tenantId, $id, [
                // withTrashed: produto/fornecedor podem ter sido excluídos (soft
                // delete) depois do lote existir — histórico ainda precisa mostrar.
                'product' => fn ($q) => $q->withTrashed()->select('id', 'name', 'sku', 'unit_of_measure', 'deleted_at'),
                'warehouse',
                'supplier' => fn ($q) => $q->withTrashed()->select('id', 'company_name', 'deleted_at'),
            ]);

            if (!$batch) {
                return ApiResponseClass::sendResponse(null, 'Lote não encontrado', 404);
            }

            return ApiResponseClass::sendResponse($batch, 'Lote recuperado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar lote');
        }
    }

    public function quarantine(Request $request, int $id): JsonResponse
    {
        return $this->updateRegulatoryStatus($request, $id, 'quarantine');
    }

    public function recall(Request $request, int $id): JsonResponse
    {
        return $this->updateRegulatoryStatus($request, $id, 'recalled');
    }

    private function updateRegulatoryStatus(Request $request, int $id, string $status): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $batch = $this->batchQueryService->findEntity($tenantId, $id);
            if (!$batch) {
                return ApiResponseClass::sendResponse(null, 'Lote não encontrado', 404);
            }

            $reason = $request->validate(['reason' => 'nullable|string|max:500'])['reason'] ?? null;

            $batch = $this->batchRegulatoryService->setStatus($batch, $status, $user->id, $reason);

            return ApiResponseClass::sendResponse(
                $this->batchQueryService->withDays($batch),
                'Status do lote atualizado',
                200
            );
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar lote');
        }
    }
}
