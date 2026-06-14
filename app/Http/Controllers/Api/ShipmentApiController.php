<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\AuthTenantService;
use App\Services\Logistics\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly ShipmentService $shipmentService,
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

    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'carrier_id'               => 'nullable|integer',
                'route_name'               => 'nullable|string|max:255',
                'driver_name'              => 'nullable|string|max:255',
                'vehicle_plate'            => 'nullable|string|max:20',
                'notes'                    => 'nullable|string',
                'sale_order_ids'           => 'sometimes|array',
                'sale_order_ids.*'         => 'required',
            ]);

            $shipment = $this->shipmentService->create($tenantId, $user->id, $validated);

            return ApiResponseClass::sendResponse($shipment, 'Romaneio criado', 201);
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
}
