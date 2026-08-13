<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\Logistics\DeliveryRouteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeliveryRouteController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly DeliveryRouteService $routeService,
    ) {}

    /**
     * GET /api/deliveries/suggest-groups?order_ids[]=1&order_ids[]=2
     */
    public function suggestGroups(Request $request): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $orderIds = array_map('intval', $request->get('order_ids', []));
            $groups = $this->routeService->groupByRegion($tenantId, $orderIds);

            return ApiResponseClass::sendResponse([
                'total_orders'  => array_sum(array_column($groups, 'stop_count')),
                'total_regions' => count($groups),
                'groups'        => array_values($groups),
            ], 'Agrupamento por região gerado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao agrupar entregas por região');
        }
    }

    /**
     * POST /api/deliveries/{shipmentId}/optimize-route
     */
    public function optimizeRoute(Request $request, int $shipmentId): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $result = $this->routeService->optimizeForTenant($tenantId, $shipmentId);

            return ApiResponseClass::sendResponse($result, 'Rota otimizada com sucesso', 200);
        } catch (ModelNotFoundException) {
            return ApiResponseClass::sendResponse(null, 'Romaneio não encontrado', 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao otimizar rota');
        }
    }

    /**
     * GET /api/deliveries/{shipmentId}/cost
     */
    public function calculateCost(Request $request, int $shipmentId): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $cost = $this->routeService->calculateCostForTenant(
                $tenantId,
                $shipmentId,
                (float) $request->get('km_per_liter', 10.0),
                (float) $request->get('fuel_price', 6.50),
                (float) $request->get('driver_cost_km', 0.50),
            );

            return ApiResponseClass::sendResponse($cost, 'Custo por entrega calculado', 200);
        } catch (ModelNotFoundException) {
            return ApiResponseClass::sendResponse(null, 'Romaneio não encontrado', 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao calcular custo de entrega');
        }
    }

    /**
     * POST /api/deliveries/{shipmentId}/reorder-stops
     */
    public function reorderStops(Request $request, int $shipmentId): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'sale_order_ids' => ['required', 'array', 'min:1'],
                'sale_order_ids.*' => ['integer', 'distinct'],
            ]);

            $result = $this->routeService->reorderStopsForTenant(
                $tenantId,
                $shipmentId,
                $validated['sale_order_ids'],
            );

            return ApiResponseClass::sendResponse($result, 'Ordem das paradas atualizada', 200);
        } catch (ValidationException $ex) {
            return ApiResponseClass::validationError(
                $ex->errors(),
                'Ordem das paradas inválida'
            );
        } catch (ModelNotFoundException) {
            return ApiResponseClass::sendResponse(null, 'Romaneio não encontrado', 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao reordenar paradas');
        }
    }

    /**
     * PATCH /api/deliveries/{shipmentId}/orders/{orderId}/window
     */
    public function setDeliveryWindow(Request $request, int $shipmentId, int $orderId): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'window_start' => ['required', 'date_format:H:i'],
                'window_end'   => ['required', 'date_format:H:i'],
            ]);

            if ($validated['window_end'] <= $validated['window_start']) {
                return ApiResponseClass::validationError([
                    'window_end' => ['O horário final deve ser posterior ao horário inicial.'],
                ], 'Janela de entrega inválida');
            }

            $this->routeService->setDeliveryWindowForTenant(
                $tenantId,
                $shipmentId,
                $orderId,
                $this->normalizeTime($validated['window_start']),
                $this->normalizeTime($validated['window_end']),
            );

            return ApiResponseClass::sendResponse(null, 'Janela de entrega definida', 200);
        } catch (ValidationException $ex) {
            return ApiResponseClass::validationError(
                $ex->errors(),
                'Janela de entrega inválida'
            );
        } catch (ModelNotFoundException $ex) {
            $message = $ex->getModel() === \App\Models\SaleOrder::class
                ? 'Pedido não pertence a este romaneio'
                : 'Romaneio não encontrado';

            return ApiResponseClass::sendResponse(null, $message, 404);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao definir janela de entrega');
        }
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }
}
