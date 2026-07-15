<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Services\AuthTenantService;
use App\Services\Stock\StockMovementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly StockMovementService $stockMovementService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $perPage   = min((int) $request->get('per_page', 50), 100);
            $paginated = $this->stockMovementService->list(
                $tenantId,
                $request->only(['type', 'product_id', 'warehouse_id']),
                $perPage
            );

            return response()->json([
                'success' => true,
                'data'    => $paginated->items(),
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
                'message' => 'Movimentações recuperadas com sucesso',
            ], 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar movimentações de estoque');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'type'            => 'required|string|in:entrada,ajuste,transferencia,devolucao',
                'product_id'      => 'required_if:type,entrada,devolucao|integer',
                'warehouse_id'    => 'required_if:type,entrada,devolucao|integer',
                'batch_id'        => 'required_if:type,ajuste,transferencia|integer',
                'quantity'        => 'required|numeric',
                'to_warehouse_id' => 'required_if:type,transferencia|integer',
                'batch_number'     => 'nullable|string|max:100',
                'manufacture_date' => 'nullable|date',
                'expiry_date'      => 'nullable|date',
                'unit_cost'        => 'nullable|numeric|min:0',
                'notes'            => 'nullable|string|max:500',
            ]);

            $result = $this->stockMovementService->record($tenantId, $user->id, $validated);

            return ApiResponseClass::sendResponse($result, 'Movimentação registrada com sucesso', 201);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao registrar movimentação');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $movement = StockMovement::forTenant($tenantId)
                ->with([
                    'product:id,name,sku,unit_of_measure',
                    'batch:id,batch_number,manufacture_date,expiry_date,status',
                    'warehouse:id,name',
                    'performedBy:id,name',
                ])
                ->findOrFail($id);

            return ApiResponseClass::sendResponse($movement, 'Movimentação encontrada');
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Movimentação não encontrada'], 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar movimentação');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $movement = StockMovement::forTenant($tenantId)->findOrFail($id);
            $movement->delete();

            return response()->json(['success' => true, 'message' => 'Movimentação excluída com sucesso'], 200);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Movimentação não encontrada'], 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir movimentação');
        }
    }
}
