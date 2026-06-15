<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\AuthTenantService;
use App\Services\Logistics\DeliveryRouteService;
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
     *
     * Suggests how to group pending sale orders by geographic region.
     */
    public function suggestGroups(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

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
     *
     * Calculates the optimal stop sequence for a shipment,
     * considering delivery windows and geographic proximity.
     */
    public function optimizeRoute(Request $request, int $shipmentId): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)
                ->with('saleOrders.client')
                ->findOrFail($shipmentId);

            $result = $this->routeService->optimizeRoute($shipment);

            return ApiResponseClass::sendResponse($result, 'Rota otimizada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao otimizar rota');
        }
    }

    /**
     * GET /api/deliveries/{shipmentId}/cost?km_per_liter=10&fuel_price=6.50&driver_cost_km=0.50
     *
     * Calculates the delivery cost breakdown for a shipment.
     */
    public function calculateCost(Request $request, int $shipmentId): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)
                ->with('saleOrders')
                ->findOrFail($shipmentId);

            $cost = $this->routeService->calculateCost(
                $shipment,
                (float) $request->get('km_per_liter', 10.0),
                (float) $request->get('fuel_price', 6.50),
                (float) $request->get('driver_cost_km', 0.50),
            );

            return ApiResponseClass::sendResponse($cost, 'Custo por entrega calculado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao calcular custo de entrega');
        }
    }

    /**
     * PATCH /api/deliveries/{shipmentId}/orders/{orderId}/window
     *
     * Sets the delivery time window for a specific order within a shipment.
     */
    public function setDeliveryWindow(Request $request, int $shipmentId, int $orderId): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'window_start' => ['required', 'date_format:H:i'],
                'window_end'   => ['required', 'date_format:H:i'],
            ]);

            if ($validated['window_end'] <= $validated['window_start']) {
                return ApiResponseClass::validationError([
                    'window_end' => ['O horário final deve ser posterior ao horário inicial.'],
                ], 'Janela de entrega inválida');
            }

            $shipment = Shipment::forTenant($tenantId)->findOrFail($shipmentId);

            $linked = $shipment->saleOrders()->where('sale_orders.id', $orderId)->exists();
            if (!$linked) {
                return ApiResponseClass::sendResponse(null, 'Pedido não pertence a este romaneio', 404);
            }

            $this->routeService->setDeliveryWindow(
                $shipment,
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
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao definir janela de entrega');
        }
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }
}
