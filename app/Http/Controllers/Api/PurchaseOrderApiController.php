<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\Purchase\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly PurchaseOrderService $purchaseOrderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $perPage   = min((int) $request->get('per_page', 50), 100);
            $paginated = $this->purchaseOrderService->list($tenantId, $request->get('status'), $perPage);

            return response()->json([
                'success' => true,
                'data'    => $paginated->items(),
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
                'message' => 'Pedidos de compra recuperados com sucesso',
            ], 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar pedidos de compra');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'supplier_id'        => 'nullable|integer',
                'expected_delivery'  => 'nullable|date',
                'payment_term_days'  => 'sometimes|integer|min:0',
                'payment_method'     => 'nullable|string|max:50',
                'freight_amount'     => 'sometimes|numeric|min:0',
                'discount_amount'    => 'sometimes|numeric|min:0',
                'notes'              => 'nullable|string',
                'items'              => 'sometimes|array',
                'items.*.product_id'       => 'required_with:items|integer',
                'items.*.quantity_ordered' => 'required_with:items|numeric|min:0.001',
                'items.*.unit_cost'        => 'required_with:items|numeric|min:0',
            ]);

            $items = $validated['items'] ?? [];
            unset($validated['items']);

            $order = $this->purchaseOrderService->create($tenantId, $validated, $items);

            return ApiResponseClass::sendResponse($order, 'Pedido de compra criado com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar pedido de compra');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = $this->purchaseOrderService->find($tenantId, $id, [
                'supplier', 'items.product', 'approvedBy:id,name',
            ]);

            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            return ApiResponseClass::sendResponse($order, 'Pedido recuperado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar pedido');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'supplier_id'       => 'sometimes|integer',
                'expected_delivery' => 'nullable|date',
                'payment_term_days' => 'sometimes|integer|min:0',
                'payment_method'    => 'nullable|string|max:50',
                'freight_amount'    => 'sometimes|numeric|min:0',
                'discount_amount'   => 'sometimes|numeric|min:0',
                'notes'             => 'nullable|string',
                'items'                    => 'sometimes|array',
                'items.*.product_id'       => 'required_with:items|integer',
                'items.*.quantity_ordered' => 'required_with:items|numeric|min:0.001',
                'items.*.unit_cost'        => 'required_with:items|numeric|min:0',
            ]);

            $result = $this->purchaseOrderService->update($tenantId, $id, $validated);

            return match ($result) {
                'not_found'    => ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404),
                'not_editable' => ApiResponseClass::sendResponse(null, 'Pedido não pode ser editado neste status', 422),
                default        => ApiResponseClass::sendResponse(
                    $this->purchaseOrderService->find($tenantId, $id),
                    'Pedido atualizado com sucesso',
                    200
                ),
            };
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar pedido');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $result = $this->purchaseOrderService->delete($tenantId, $id);

            return match ($result) {
                'not_found'     => ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404),
                'not_deletable' => ApiResponseClass::sendResponse(null, 'Apenas pedidos em rascunho ou cancelados podem ser excluídos', 422),
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

            $order = $this->purchaseOrderService->advanceStatus($tenantId, $id);

            return ApiResponseClass::sendResponse($order, 'Status avançado com sucesso', 200);
        } catch (\InvalidArgumentException $ex) {
            return ApiResponseClass::sendResponse(null, $ex->getMessage(), 404);
        } catch (\DomainException|StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao avançar status');
        }
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = $this->purchaseOrderService->find($tenantId, $id, ['items']);
            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }

            $validated = $request->validate([
                'items'                          => 'required|array|min:1',
                'items.*.purchase_order_item_id' => 'required|integer',
                'items.*.quantity'               => 'required|numeric|min:0.001',
                'items.*.warehouse_id'           => 'required|integer',
                'items.*.batch_number'           => 'nullable|string|max:100',
                'items.*.expiry_date'            => 'nullable|date',
            ]);

            $order = $this->purchaseOrderService->receive($order, $validated['items'], $user->id);

            return ApiResponseClass::sendResponse($order, 'Recebimento registrado com sucesso', 200);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao registrar recebimento');
        }
    }
}
