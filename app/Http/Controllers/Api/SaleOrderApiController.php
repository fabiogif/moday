<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\CreditException;
use App\Exceptions\FiscalException;
use App\Exceptions\RegulatoryException;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Rules\DiscountWithinProfileLimit;
use App\Rules\ValidBatchForSale;
use App\Services\AuthTenantService;
use App\Services\Fiscal\FiscalIntegrationService;
use App\Services\Sale\SaleOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleOrderApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly SaleOrderService $saleOrderService,
        private readonly FiscalIntegrationService $fiscalIntegrationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $perPage   = min((int) $request->get('per_page', 50), 100);
            $search    = $request->get('search');
            $search    = is_string($search) ? trim($search) : null;
            $search    = $search !== '' ? $search : null;
            $paginated = $this->saleOrderService->list($tenantId, $request->get('status'), $perPage, $search);

            return response()->json([
                'success' => true,
                'data'    => $paginated->items(),
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
                'message' => 'Pedidos de venda recuperados com sucesso',
            ], 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar pedidos de venda');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $discountRule = DiscountWithinProfileLimit::forUser($user);

            $validated = $request->validate([
                'offline_id'               => 'nullable|string|max:100',
                'client_id'                => 'nullable|integer',
                'status'                   => 'sometimes|string',
                'payment_term_days'        => 'sometimes|integer|min:0',
                'payment_method'           => 'nullable|string|max:50',
                'installments'             => 'sometimes|integer|min:1|max:48',
                'freight_amount'           => 'sometimes|numeric|min:0',
                'discount_amount'          => ['sometimes', 'numeric', 'min:0', $discountRule],
                'prescription_verified'    => 'sometimes|boolean',
                'notes'                    => 'nullable|string',
                'items'                    => 'sometimes|array',
                'items.*.product_id'       => 'required_with:items|integer',
                'items.*.batch_id'         => ['nullable', 'integer', new ValidBatchForSale()],
                'items.*.quantity'         => 'required_with:items|numeric|min:0.001',
                'items.*.unit_price'       => 'nullable|numeric|min:0',
                'items.*.item_type'        => 'sometimes|string|in:venda,bonificacao',
                'items.*.discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100', $discountRule],
                'use_client_address'       => 'sometimes|boolean',
                'shipping_address'         => 'nullable|array',
                'shipping_address.street'    => 'nullable|string|max:255',
                'shipping_address.number'    => 'nullable|string|max:30',
                'shipping_address.neighborhood' => 'nullable|string|max:120',
                'shipping_address.complement'   => 'nullable|string|max:120',
                'shipping_city'            => 'nullable|string|max:120',
                'shipping_state'           => 'nullable|string|max:2',
                'shipping_zipcode'         => 'nullable|string|max:20',
            ]);

            $items = $validated['items'] ?? [];
            $order = $this->saleOrderService->create($tenantId, $user->id, $validated, $items);
            $order->load(['client:id,company_name,trade_name', 'items.product:id,name,sku']);

            return ApiResponseClass::sendResponse($order, 'Pedido de venda criado com sucesso', 201);
        } catch (StockException|CreditException|RegulatoryException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\DomainException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar pedido de venda');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = $this->saleOrderService->find($tenantId, $id, [
                'client', 'items.product', 'items.batch', 'approvedBy:id,name', 'shipments',
            ]);

            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            $orderData = $order->toArray();
            $orderData['shipments'] = $order->shipments->map(function ($shipment) {
                $pivot = $shipment->pivot;
                return [
                    'id'          => $shipment->id,
                    'identify'    => $shipment->identify,
                    'status'      => $shipment->status,
                    'driver_name' => $shipment->driver_name,
                    'vehicle_plate' => $shipment->vehicle_plate,
                    'shipped_at'  => $shipment->shipped_at?->format('d/m/Y H:i'),
                    'delivered_at' => $shipment->delivered_at?->format('d/m/Y H:i'),
                    'pod' => [
                        'status'         => $pivot->pod_status,
                        'recipient_name' => $pivot->pod_recipient_name,
                        'delivered_at'   => $pivot->pod_delivered_at
                            ? \Carbon\Carbon::parse($pivot->pod_delivered_at)->format('d/m/Y H:i')
                            : null,
                        'notes'          => $pivot->pod_notes,
                        'photo_url'      => $pivot->pod_photo_path
                            ? url(\Illuminate\Support\Facades\Storage::disk('public')->url($pivot->pod_photo_path))
                            : null,
                        'signature_url'  => $pivot->pod_signature_path
                            ? url(\Illuminate\Support\Facades\Storage::disk('public')->url($pivot->pod_signature_path))
                            : null,
                    ],
                ];
            })->values()->toArray();

            return ApiResponseClass::sendResponse($orderData, 'Pedido recuperado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar pedido');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $discountRule = DiscountWithinProfileLimit::forUser($user);

            $validated = $request->validate([
                'status'                 => 'sometimes|string',
                'payment_term_days'      => 'sometimes|integer|min:0',
                'payment_method'         => 'nullable|string|max:50',
                'installments'           => 'sometimes|integer|min:1|max:48',
                'freight_amount'         => 'sometimes|numeric|min:0',
                'discount_amount'        => ['sometimes', 'numeric', 'min:0', $discountRule],
                'prescription_verified'  => 'sometimes|boolean',
                'notes'                  => 'nullable|string',
                'nfe_number'             => 'nullable|string|max:50',
                'items'                    => 'sometimes|array',
                'items.*.product_id'       => 'required_with:items|integer',
                'items.*.batch_id'         => ['nullable', 'integer', new ValidBatchForSale()],
                'items.*.quantity'         => 'required_with:items|numeric|min:0.001',
                'items.*.unit_price'       => 'nullable|numeric|min:0',
                'items.*.item_type'        => 'sometimes|string|in:venda,bonificacao',
                'items.*.discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100', $discountRule],
                'use_client_address'     => 'sometimes|boolean',
                'shipping_address'       => 'nullable|array',
                'shipping_address.street'    => 'nullable|string|max:255',
                'shipping_address.number'    => 'nullable|string|max:30',
                'shipping_address.neighborhood' => 'nullable|string|max:120',
                'shipping_address.complement'   => 'nullable|string|max:120',
                'shipping_city'          => 'nullable|string|max:120',
                'shipping_state'         => 'nullable|string|max:2',
                'shipping_zipcode'       => 'nullable|string|max:20',
            ]);

            $order = $this->saleOrderService->update($tenantId, $id, $validated);
            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            return ApiResponseClass::sendResponse($order, 'Pedido atualizado com sucesso', 200);
        } catch (\DomainException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar pedido');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $result = $this->saleOrderService->delete($tenantId, $id);

            return match ($result) {
                'not_found'     => ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404),
                'not_deletable' => ApiResponseClass::sendResponse(null, 'Apenas pedidos em orçamento ou cancelados podem ser excluídos', 422),
                default         => ApiResponseClass::sendResponse(null, 'Pedido excluído com sucesso', 200),
            };
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir pedido');
        }
    }

    public function advanceStatus(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = $this->saleOrderService->advanceStatus($tenantId, $id, $user->id);

            return ApiResponseClass::sendResponse($order, 'Status avançado com sucesso', 200);
        } catch (\InvalidArgumentException $ex) {
            return ApiResponseClass::sendResponse(null, $ex->getMessage(), 404);
        } catch (\DomainException $ex) {
            return ApiResponseClass::sendResponse(null, $ex->getMessage(), 422);
        } catch (StockException|CreditException|RegulatoryException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao avançar status');
        }
    }

    public function returnItems(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = $this->saleOrderService->find($tenantId, $id);
            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            $validated = $request->validate([
                'items'                      => 'required|array|min:1',
                'items.*.sale_order_item_id' => 'required|integer',
                'items.*.quantity'           => 'required|numeric|min:0.001',
                'items.*.warehouse_id'       => 'required|integer',
            ]);

            $order = $this->saleOrderService->returnItems($order, $validated['items'], $user->id);

            return ApiResponseClass::sendResponse($order, 'Devolução registrada com sucesso', 200);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao registrar devolução');
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $result = $this->saleOrderService->cancel($tenantId, $id);

            return match ($result) {
                'not_found'        => ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404),
                'not_cancellable'  => ApiResponseClass::sendResponse(null, 'Pedido não pode ser cancelado', 422),
                'billed'           => ApiResponseClass::sendResponse(null, 'Pedido faturado não pode ser cancelado sem devolução', 422),
                default            => ApiResponseClass::sendResponse(
                    $this->saleOrderService->find($tenantId, $id),
                    'Pedido cancelado com sucesso',
                    200
                ),
            };
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao cancelar pedido');
        }
    }

    public function requestFiscalEmission(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = $this->saleOrderService->find($tenantId, $id);
            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            $result = $this->fiscalIntegrationService->requestEmission($order, $user->id);

            return ApiResponseClass::sendResponse($result, 'Emissão fiscal solicitada ao provedor', 200);
        } catch (FiscalException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao solicitar emissão fiscal');
        }
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'scheduled_at' => 'required|date|after:now',
            ]);

            $order = $this->saleOrderService->find($tenantId, $id);
            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            if ($order->status !== 'orcamento') {
                return response()->json(['success' => false, 'message' => 'Apenas pedidos em orçamento podem ser agendados'], 422);
            }

            $order->update([
                'scheduled_at' => $validated['scheduled_at'],
                'is_scheduled' => true,
            ]);

            return ApiResponseClass::sendResponse($order->fresh(), 'Pedido agendado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao agendar pedido');
        }
    }

    public function cancelSchedule(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = $this->saleOrderService->find($tenantId, $id);
            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            $order->update(['scheduled_at' => null, 'is_scheduled' => false]);

            return ApiResponseClass::sendResponse($order->fresh(), 'Agendamento cancelado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao cancelar agendamento');
        }
    }

    public function calendarLink(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = $this->saleOrderService->find($tenantId, $id, ['client']);
            if (!$order || !$order->scheduled_at) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado ou não agendado', 404);
            }

            $start  = $order->scheduled_at->format('Ymd\THis\Z');
            $end    = $order->scheduled_at->addHour()->format('Ymd\THis\Z');
            $client = $order->client?->trade_name ?? $order->client?->company_name ?? 'Cliente';
            $title  = urlencode("Pedido {$order->identify} — {$client}");
            $details= urlencode("Pedido de venda {$order->identify}. Total: R$ " . number_format((float) $order->total, 2, ',', '.'));

            $googleUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$start}/{$end}&details={$details}";

            $ics = implode("\r\n", [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'PRODID:-//DistribTec//EN',
                'BEGIN:VEVENT',
                "DTSTART:{$start}",
                "DTEND:{$end}",
                "SUMMARY:Pedido {$order->identify} — {$client}",
                "DESCRIPTION:Total: R$ " . number_format((float) $order->total, 2, ',', '.'),
                'END:VEVENT',
                'END:VCALENDAR',
            ]);

            return ApiResponseClass::sendResponse([
                'google_url' => $googleUrl,
                'ics_data'   => base64_encode($ics),
                'filename'   => "pedido-{$order->identify}.ics",
            ], 'Link de agenda gerado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar link de agenda');
        }
    }
}
