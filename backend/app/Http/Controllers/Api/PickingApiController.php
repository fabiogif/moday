<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Models\SaleOrder;
use App\Services\AuthTenantService;
use App\Services\Logistics\PickingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickingApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly PickingService $pickingService,
    ) {}

    public function show(int $saleOrderId): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = SaleOrder::forTenant($tenantId)->find($saleOrderId);
            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                $this->pickingService->getPickingList($order),
                'Lista de separação recuperada',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar separação');
        }
    }

    public function confirm(Request $request, int $saleOrderId): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = SaleOrder::forTenant($tenantId)->find($saleOrderId);
            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            $validated = $request->validate([
                'items'                          => 'required|array|min:1',
                'items.*.sale_order_item_id'     => 'required|integer',
                'items.*.quantity_picked'        => 'required|numeric|min:0',
            ]);

            $order = $this->pickingService->confirmPicking($order, $validated['items'], $user->id);

            return ApiResponseClass::sendResponse($order, 'Separação confirmada', 200);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao confirmar separação');
        }
    }
}
