<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Requests\Api\TenantFormRequest;
use App\Http\Requests\Api\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;


class OrderApiController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {

    }

    public function store(StoreOrderRequest  $request):JsonResponse
    {
        try {
            $order = $this->orderService->createNewOrder($request->all());
            return ApiResponseClass::sendResponse(new OrderResource($order), 'Pedido cadastrado com sucesso', Response::HTTP_CREATED);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function show($identify):JsonResponse
    {
        try {
            $order = $this->orderService->getOrderByIdentify($identify);
            if(!$order) {
                return ApiResponseClass::sendResponse('', 'Pedido não encontrado', 404);
            }
            
            // Ensure relationships are loaded
            $order->load(['client', 'table', 'products', 'tenant']);
            
            return ApiResponseClass::sendResponse(new OrderResource($order), '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar pedido');
        }
    }

    public function orderByClient(): AnonymousResourceCollection|JsonResponse
    {
        try {
            $order = $this->orderService->ordersByClient();
            if (!$order) {
                return ApiResponseClass::sendResponse([], 'Nenhum pedido encontrado', 404);
            }
            return ApiResponseClass::sendResponsePaginate(OrderResource::class, $order, 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar pedidos do cliente');
        }
    }

    /**
     * Lista pedidos paginados por tenant autenticado
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 15);
            $status = $request->get('status');

            $orders = $this->orderService->paginateByTenant($tenantId, $page, $perPage, $status);

            return ApiResponseClass::sendResponsePaginate(OrderResource::class, $orders, 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar pedidos');
        }
    }

    /**
     * Fatura um pedido
     */
    public function invoice($identify): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderByIdentify($identify);
            if (!$order) {
                return ApiResponseClass::sendResponse('', 'Pedido não encontrado', 404);
            }

            // Verificar se o pedido já foi faturado/entregue
            if ($order->status === 'Entregue') {
                return ApiResponseClass::sendResponse('', 'Pedido já foi entregue/faturado', 400);
            }

            // Atualizar status para entregue (faturado)
            $order->update(['status' => 'Entregue']);

            return ApiResponseClass::sendResponse(new OrderResource($order), 'Pedido faturado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao faturar pedido');
        }
    }

    /**
     * Gera recibo do pedido
     */
    public function receipt($identify): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderByIdentify($identify);
            if (!$order) {
                return ApiResponseClass::sendResponse('', 'Pedido não encontrado', 404);
            }

            // Retornar dados do pedido para geração do recibo
            return ApiResponseClass::sendResponse(new OrderResource($order), 'Dados do recibo obtidos com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar recibo');
        }
    }

    /**
     * Update order
     */
    public function update(UpdateOrderRequest $request, $identify): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrder($identify, $request->validated());
            return ApiResponseClass::sendResponse(new OrderResource($order), 'Pedido atualizado com sucesso', Response::HTTP_OK);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar pedido');
        }
    }

    public function delete($identify): JsonResponse
    {
        try {
            $this->orderService->deleteOrder($identify);
            return ApiResponseClass::sendResponse(null, 'Pedido excluído com sucesso', Response::HTTP_NO_CONTENT);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

}

