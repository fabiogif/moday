<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Models\InventoryCount;
use App\Services\AuthTenantService;
use App\Services\Stock\InventoryCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryCountApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly InventoryCountService $inventoryCountService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $counts = InventoryCount::forTenant($tenantId)
                ->with('warehouse:id,name')
                ->withCount('items')
                ->latest()
                ->get();

            return ApiResponseClass::sendResponse($counts, 'Inventários recuperados', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar inventários');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'warehouse_id' => 'required|integer',
                'notes'        => 'nullable|string',
            ]);

            $count = $this->inventoryCountService->open(
                $tenantId,
                (int) $validated['warehouse_id'],
                $user->id,
                $validated['notes'] ?? null
            );

            return ApiResponseClass::sendResponse($count, 'Inventário aberto', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao abrir inventário');
        }
    }

    public function addItem(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $count = InventoryCount::forTenant($tenantId)->find($id);
            if (!$count) {
                return ApiResponseClass::sendResponse(null, 'Inventário não encontrado', 404);
            }

            $validated = $request->validate([
                'product_id'       => 'required|integer',
                'batch_id'         => 'nullable|integer',
                'counted_quantity' => 'required|numeric|min:0',
                'notes'            => 'nullable|string',
            ]);

            $item = $this->inventoryCountService->addItem($count, $validated);

            return ApiResponseClass::sendResponse($item, 'Item contado registrado', 201);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao registrar contagem');
        }
    }

    public function close(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $count = InventoryCount::forTenant($tenantId)->with('items')->find($id);
            if (!$count) {
                return ApiResponseClass::sendResponse(null, 'Inventário não encontrado', 404);
            }

            $count = $this->inventoryCountService->close($count, $user->id);

            return ApiResponseClass::sendResponse($count, 'Inventário encerrado com ajustes', 200);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao encerrar inventário');
        }
    }
}
