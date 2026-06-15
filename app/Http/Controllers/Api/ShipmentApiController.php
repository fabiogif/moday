<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\AuthTenantService;
use App\Services\Logistics\DeliveryRouteService;
use App\Services\Logistics\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly ShipmentService $shipmentService,
        private readonly DeliveryRouteService $deliveryRouteService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipments = Shipment::forTenant($tenantId)
                ->with(['carrier', 'saleOrders:id,identify,status'])
                ->latest()
                ->paginate(50);

            return response()->json([
                'success' => true,
                'data'    => $shipments->items(),
                'meta'    => [
                    'current_page' => $shipments->currentPage(),
                    'total'        => $shipments->total(),
                ],
                'message' => 'Romaneios recuperados',
            ]);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar romaneios');
        }
    }

    public function pendingOrders(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $orders = $this->shipmentService->listPendingOrders($tenantId);

            return ApiResponseClass::sendResponse($orders, 'Pedidos disponíveis para expedição', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar pedidos para expedição');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)
                ->with(['carrier', 'saleOrders.client'])
                ->find($id);

            if (!$shipment) {
                return ApiResponseClass::sendResponse(null, 'Romaneio não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                $this->formatShipmentDetail($shipment),
                'Romaneio recuperado',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao recuperar romaneio');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'carrier_id'               => 'nullable|integer',
                'vehicle_id'               => 'nullable|integer',
                'driver_id'                => 'nullable|integer',
                'route_name'               => 'nullable|string|max:255',
                'driver_name'              => 'nullable|string|max:255',
                'vehicle_plate'            => 'nullable|string|max:20',
                'notes'                    => 'nullable|string',
                'sale_order_ids'           => 'sometimes|array',
                'sale_order_ids.*'         => 'required',
                'auto_optimize'            => 'sometimes|boolean',
            ]);

            $shipment = $this->shipmentService->create($tenantId, $user->id, $validated);

            if ($request->boolean('auto_optimize')) {
                $this->deliveryRouteService->optimizeRoute($shipment->fresh(['saleOrders.client', 'tenant']));
            }

            return ApiResponseClass::sendResponse(
                $this->formatShipmentDetail($shipment->fresh(['carrier', 'saleOrders.client'])),
                'Romaneio criado',
                201
            );
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar romaneio');
        }
    }

    public function dispatch(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)->find($id);
            if (!$shipment) {
                return ApiResponseClass::sendResponse(null, 'Romaneio não encontrado', 404);
            }

            $shipment = $this->shipmentService->dispatch($shipment, $user->id);

            return ApiResponseClass::sendResponse($shipment, 'Romaneio expedido', 200);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao expedir romaneio');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)->find($id);
            if (!$shipment) {
                return ApiResponseClass::sendResponse(null, 'Romaneio não encontrado', 404);
            }

            if ($shipment->status === 'dispatched') {
                return ApiResponseClass::sendResponse(null, 'Romaneio em trânsito não pode ser removido', 422);
            }

            $shipment->delete();

            return ApiResponseClass::sendResponse(null, 'Romaneio removido com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover romaneio');
        }
    }

    public function deliver(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)->find($id);
            if (!$shipment) {
                return ApiResponseClass::sendResponse(null, 'Romaneio não encontrado', 404);
            }

            $validated = $request->validate([
                'pod_reference' => 'nullable|string|max:255',
            ]);

            $shipment = $this->shipmentService->deliver(
                $shipment,
                $user->id,
                $validated['pod_reference'] ?? null
            );

            return ApiResponseClass::sendResponse($shipment, 'Entrega confirmada', 200);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao confirmar entrega');
        }
    }

    private function formatShipmentDetail(Shipment $shipment): array
    {
        return [
            'id' => $shipment->id,
            'identify' => $shipment->identify,
            'route_name' => $shipment->route_name,
            'driver_name' => $shipment->driver_name,
            'vehicle_plate' => $shipment->vehicle_plate,
            'status' => $shipment->status,
            'region' => $shipment->region,
            'estimated_km' => $shipment->estimated_km,
            'estimated_duration_minutes' => $shipment->estimated_duration_minutes,
            'delivery_cost' => $shipment->delivery_cost,
            'cost_per_delivery' => $shipment->cost_per_delivery,
            'optimized_route' => $shipment->optimized_route,
            'route_polyline' => $shipment->route_polyline,
            'carrier' => $shipment->carrier,
            'sale_orders' => $shipment->saleOrders->map(fn ($order) => [
                'id' => $order->id,
                'identify' => $order->identify,
                'status' => $order->status,
                'shipping_city' => $order->shipping_city,
                'shipping_state' => $order->shipping_state,
                'shipping_zipcode' => $order->shipping_zipcode,
                'client' => $order->client ? [
                    'name' => $order->client->name,
                    'company_name' => $order->client->company_name,
                    'trade_name' => $order->client->trade_name,
                ] : null,
                'pivot' => [
                    'delivery_sequence' => $order->pivot->delivery_sequence,
                    'delivery_window_start' => $order->pivot->delivery_window_start,
                    'delivery_window_end' => $order->pivot->delivery_window_end,
                    'delivery_zipcode' => $order->pivot->delivery_zipcode,
                ],
            ])->values(),
        ];
    }
}
