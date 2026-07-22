<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentOccurrence;
use App\Services\AuthTenantService;
use App\Services\DeliveryLinkWhatsAppService;
use App\Services\Logistics\DeliveryRouteService;
use App\Services\Logistics\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShipmentApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly ShipmentService $shipmentService,
        private readonly DeliveryRouteService $deliveryRouteService,
        private readonly DeliveryLinkWhatsAppService $deliveryLinkWhatsApp,
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
                ->with(['carrier', 'driver', 'saleOrders.client', 'occurrences', 'tenant.plan'])
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

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)
                ->with(['carrier', 'saleOrders.client', 'occurrences'])
                ->find($id);

            if (!$shipment) {
                return ApiResponseClass::sendResponse(null, 'Romaneio não encontrado', 404);
            }

            if ($shipment->status === 'dispatched') {
                return response()->json(['success' => false, 'message' => 'Romaneios em trânsito não podem ser editados'], 422);
            }

            if ($shipment->status === 'delivered' && !$user->hasProfile('administrador')) {
                return response()->json(['success' => false, 'message' => 'Apenas administradores podem editar romaneios entregues'], 403);
            }

            $validated = $request->validate([
                'route_name'    => 'nullable|string|max:255',
                'driver_name'   => 'nullable|string|max:255',
                'vehicle_plate' => 'nullable|string|max:20',
                'notes'         => 'nullable|string',
                'vehicle_id'    => 'nullable|integer',
                'driver_id'     => 'nullable|integer',
            ]);

            $shipment->update($validated);

            return ApiResponseClass::sendResponse(
                $this->formatShipmentDetail($shipment->fresh(['carrier', 'saleOrders.client', 'occurrences'])),
                'Romaneio atualizado',
                200
            );
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage(), 'errors' => $ex->errors()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar romaneio');
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

            return ApiResponseClass::sendResponse(
                $this->formatShipmentDetail($shipment),
                'Romaneio expedido',
                200
            );
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao expedir romaneio');
        }
    }

    public function sendDeliveryLink(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)
                ->with(['carrier', 'driver', 'saleOrders.client', 'tenant.plan'])
                ->find($id);

            if (!$shipment) {
                return ApiResponseClass::sendResponse(null, 'Romaneio n�o encontrado', 404);
            }

            if ($shipment->status !== 'dispatched') {
                return response()->json([
                    'success' => false,
                    'message' => 'O link de entrega s� pode ser enviado para romaneios em tr�nsito.',
                ], 422);
            }

            $frontendUrl = config('app.frontend_url', config('app.url'));
            $this->deliveryLinkWhatsApp->send($shipment, $frontendUrl);

            return ApiResponseClass::sendResponse(null, 'Link de entrega enviado para o motorista via WhatsApp', 200);
        } catch (\RuntimeException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao enviar link de entrega para o motorista');
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

    public function storeOccurrence(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)->find($id);
            if (!$shipment) {
                return ApiResponseClass::sendResponse(null, 'Romaneio não encontrado', 404);
            }

            $validated = $request->validate([
                'type'         => 'required|in:delay,damage,refused,absent,other',
                'description'  => 'required|string|max:1000',
                'sale_order_id' => 'nullable|integer',
            ]);

            $occurrence = ShipmentOccurrence::create([
                'shipment_id'   => $shipment->id,
                'sale_order_id' => $validated['sale_order_id'] ?? null,
                'type'          => $validated['type'],
                'description'   => $validated['description'],
                'occurred_at'   => now(),
                'created_by'    => $user->id,
            ]);

            return ApiResponseClass::sendResponse($occurrence, 'Ocorrência registrada', 201);
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage(), 'errors' => $ex->errors()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao registrar ocorrência');
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
        $occurrenceTypeLabels = [
            'delay'   => 'Atraso',
            'damage'  => 'Avaria',
            'refused' => 'Recusa',
            'absent'  => 'Ausência',
            'other'   => 'Outro',
        ];

        $depotAddress = null;
        if ($shipment->relationLoaded('tenant') && $shipment->tenant) {
            $t = $shipment->tenant;
            $parts = array_filter([$t->address, $t->city, $t->state, $t->zipcode]);
            if ($parts) {
                $depotAddress = implode(', ', $parts);
            }
        }

        $driverPhone = $shipment->driver?->phone;
        $hasAutoWhatsApp = (bool) ($shipment->tenant?->plan?->has_whatsapp_notifications ?? false);

        return [
            'id' => $shipment->id,
            'identify' => $shipment->identify,
            'route_name' => $shipment->route_name,
            'driver_name' => $shipment->driver_name,
            'driver_phone' => $driverPhone,
            'vehicle_plate' => $shipment->vehicle_plate,
            'status' => $shipment->status,
            'has_auto_whatsapp' => $hasAutoWhatsApp,
            'delivery_token' => $shipment->delivery_token,
            'region' => $shipment->region,
            'estimated_km' => $shipment->estimated_km,
            'estimated_duration_minutes' => $shipment->estimated_duration_minutes,
            'delivery_cost' => $shipment->delivery_cost,
            'cost_per_delivery' => $shipment->cost_per_delivery,
            'freight_weight_amount' => $shipment->freight_weight_amount,
            'freight_weight_unit' => $shipment->freight_weight_unit,
            'freight_weight_charge_mode' => $shipment->freight_weight_charge_mode,
            'freight_weight_quantity' => $shipment->freight_weight_quantity,
            'freight_weight_breakdown' => $shipment->freight_weight_breakdown,
            'total_weight_kg' => $shipment->total_weight_kg,
            'total_volume_m3' => $shipment->total_volume_m3,
            'optimized_route' => $shipment->optimized_route,
            'route_order_source' => $shipment->route_order_source ?? 'system',
            'route_polyline' => $shipment->route_polyline,
            'depot_address' => $depotAddress,
            'carrier' => $shipment->carrier,
            'sale_orders' => $shipment->saleOrders->map(fn ($order) => [
                'id' => $order->id,
                'identify' => $order->identify,
                'status' => $order->status,
                'shipping_address' => $order->shipping_address,
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
                    'pod_status' => $order->pivot->pod_status,
                    'pod_recipient_name' => $order->pivot->pod_recipient_name,
                    'pod_delivered_at' => $order->pivot->pod_delivered_at
                        ? \Carbon\Carbon::parse($order->pivot->pod_delivered_at)->format('d/m/Y H:i')
                        : null,
                    'pod_notes' => $order->pivot->pod_notes,
                    'pod_photo_url' => $order->pivot->pod_photo_path
                        ? url(Storage::disk('public')->url($order->pivot->pod_photo_path))
                        : null,
                    'pod_signature_url' => $order->pivot->pod_signature_path
                        ? url(Storage::disk('public')->url($order->pivot->pod_signature_path))
                        : null,
                ],
            ])->values(),
            'occurrences' => ($shipment->relationLoaded('occurrences') ? $shipment->occurrences : collect())->map(fn ($o) => [
                'id' => $o->id,
                'type' => $o->type,
                'type_label' => $occurrenceTypeLabels[$o->type] ?? $o->type,
                'description' => $o->description,
                'occurred_at' => $o->occurred_at?->format('d/m/Y H:i'),
            ])->values(),
        ];
    }
}
