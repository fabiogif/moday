<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Requests\Api\TenantFormRequest;
use App\Http\Requests\Api\UpdateOrderRequest;
use App\Http\Requests\Api\BulkDeleteOrdersRequest;
use App\Http\Requests\Api\BulkUpdateOrdersStatusRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Services\ProductRecommendationService;
use App\Services\OrderEmailService;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;


class OrderApiController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected ProductRecommendationService $recommendationService,
        protected OrderEmailService $orderEmailService,
    ) {

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
            $order->load(['client', 'table', 'products', 'tenant', 'paymentMethod']);
            
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
            $tenantId = Auth::user()?->tenant_id;
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

            $order->load(['client', 'table', 'products', 'tenant', 'paymentMethod']);

            return ApiResponseClass::sendResponse(new OrderResource($order), 'Dados do recibo obtidos com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar recibo');
        }
    }

    /**
     * Envia recibo do pedido por e-mail ao cliente
     */
    public function sendReceiptEmail($identify): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderByIdentify($identify);
            if (!$order) {
                return ApiResponseClass::sendResponse('', 'Pedido não encontrado', 404);
            }

            if (!$order->client?->email) {
                return ApiResponseClass::validationError(
                    ['email' => ['O cliente deste pedido não possui e-mail cadastrado.']],
                    'Cliente sem e-mail cadastrado.'
                );
            }

            $this->orderEmailService->sendOrderReceiptEmail($order);

            return ApiResponseClass::sendResponse(
                ['email' => $order->client->email],
                'Recibo enviado por e-mail com sucesso.'
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao enviar recibo por e-mail');
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
        } catch (\DomainException $ex) {
            // Pedido finalizado - retornar erro específico
            return ApiResponseClass::sendResponse('', $ex->getMessage(), 403);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar pedido');
        }
    }

    public function archive($identify): JsonResponse
    {
        try {
            $order = $this->orderService->archiveOrder($identify);
            return ApiResponseClass::sendResponse(new OrderResource($order), 'Pedido arquivado com sucesso', 200);
        } catch (\DomainException $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
            ], 400);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao arquivar pedido');
        }
    }

    /**
     * Avançar status do pedido
     */
    public function advanceStatus($identify): JsonResponse
    {
        try {
            $order = $this->orderService->advanceOrderStatus($identify);
            return ApiResponseClass::sendResponse(new OrderResource($order), 'Status do pedido atualizado com sucesso', Response::HTTP_OK);
        } catch (\DomainException $ex) {
            // Pedido finalizado - retornar erro específico
            return ApiResponseClass::sendResponse('', $ex->getMessage(), 403);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao avançar status do pedido');
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

    /**
     * Excluir múltiplos pedidos em massa
     */
    public function bulkDelete(BulkDeleteOrdersRequest $request): JsonResponse
    {
        try {
            $orderIdentifies = $request->validated()['order_ids'];
            
            if (empty($orderIdentifies)) {
                return ApiResponseClass::sendResponse('', 'Nenhum pedido informado para exclusão', 400);
            }

            $result = $this->orderService->bulkDeleteOrders($orderIdentifies);

            $message = "Operação concluída. ";
            $message .= "{$result['total_deleted']} pedido(s) excluído(s) com sucesso.";
            
            if (!empty($result['not_found'])) {
                $count = is_array($result['not_found']) ? count($result['not_found']) : 0;
                $message .= " {$count} pedido(s) não encontrado(s).";
            }
            if (!empty($result['unauthorized'])) {
                $count = is_array($result['unauthorized']) ? count($result['unauthorized']) : 0;
                $message .= " {$count} pedido(s) não autorizado(s).";
            }
            if (!empty($result['errors'])) {
                $count = is_array($result['errors']) ? count($result['errors']) : 0;
                $message .= " {$count} erro(s) encontrado(s).";
            }

            return ApiResponseClass::sendResponse($result, $message, Response::HTTP_OK);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir pedidos em massa');
        }
    }

    /**
     * Atualizar status de múltiplos pedidos em massa
     */
    public function bulkUpdateStatus(BulkUpdateOrdersStatusRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $orderIdentifies = $validated['order_ids'];
            $status = $validated['status'];
            
            if (empty($orderIdentifies)) {
                return ApiResponseClass::sendResponse('', 'Nenhum pedido informado para atualização', 400);
            }

            $result = $this->orderService->bulkUpdateOrdersStatus($orderIdentifies, $status);

            $message = "Operação concluída. ";
            $message .= "{$result['total_updated']} pedido(s) atualizado(s) para status '{$status}'.";
            
            if (!empty($result['not_found'])) {
                $count = is_array($result['not_found']) ? count($result['not_found']) : 0;
                $message .= " {$count} pedido(s) não encontrado(s).";
            }
            if (!empty($result['unauthorized'])) {
                $count = is_array($result['unauthorized']) ? count($result['unauthorized']) : 0;
                $message .= " {$count} pedido(s) não autorizado(s).";
            }
            if (!empty($result['final_status'])) {
                $count = is_array($result['final_status']) ? count($result['final_status']) : 0;
                $message .= " {$count} pedido(s) com status final não podem ser alterados.";
            }
            if (!empty($result['errors'])) {
                $count = is_array($result['errors']) ? count($result['errors']) : 0;
                $message .= " {$count} erro(s) encontrado(s).";
            }

            return ApiResponseClass::sendResponse($result, $message, Response::HTTP_OK);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar status dos pedidos em massa');
        }
    }

    /**
     * Buscar pedidos por número (para autocomplete)
     */
    public function searchByNumber(Request $request): JsonResponse
    {
        try {
            $query = $request->input('query', '');
            $tenantId = Auth::user()->tenant_id;
            
            $orders = $this->orderService->searchOrdersByNumber($tenantId, $query);
            
            return ApiResponseClass::sendResponse($orders, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar pedidos');
        }
    }

    /**
     * Obter detalhes de um pedido por ID para preencher formulário
     */
    public function getOrderDetails($orderId): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $order = $this->orderService->getOrderDetailsForAccount($orderId, $tenantId);
            
            if (!$order) {
                return ApiResponseClass::sendResponse(null, 'Pedido não encontrado', 404);
            }
            
            return ApiResponseClass::sendResponse($order, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar detalhes do pedido');
        }
    }

    /**
     * Buscar pedidos em aberto por mesa
     */
    public function getOpenOrdersByTable(Request $request): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $tableUuid = $request->input('table_uuid');
            if (!$tableUuid) {
                return ApiResponseClass::sendResponse('', 'UUID da mesa é obrigatório', 400);
            }

            $orders = $this->orderService->getOpenOrdersByTable($tenantId, $tableUuid);
            
            // Transformar para recursos
            $ordersResource = collect($orders)->map(function ($orderData) {
                $order = \App\Models\Order::with(['client', 'table', 'products', 'orderStatus', 'paymentMethod'])->find($orderData['id']);
                return $order ? new OrderResource($order) : null;
            })->filter();

            return ApiResponseClass::sendResponse($ordersResource->values(), 'Pedidos carregados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar pedidos da mesa');
        }
    }

    /**
     * Buscar pedidos do dia atual (para PDV)
     */
    public function getTodayOrders(): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $orders = $this->orderService->getTodayOrders($tenantId);
            
            // Transformar para recursos
            $ordersResource = collect($orders)->map(function ($orderData) {
                $order = \App\Models\Order::with(['client', 'table', 'products', 'orderStatus', 'paymentMethod'])->find($orderData['id']);
                return $order ? new OrderResource($order) : null;
            })->filter();

            return ApiResponseClass::sendResponse($ordersResource->values(), 'Pedidos do dia carregados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar pedidos do dia');
        }
    }

    /**
     * Obter recomendações de produtos baseadas no carrinho atual
     * 
     * Endpoint otimizado para retornar produtos frequentemente pedidos juntos
     * ou produtos mais vendidos como fallback
     */
    public function getProductRecommendations(Request $request): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $cartProductIds = $request->input('product_ids', []);
            $limit = (int) $request->input('limit', 6);

            if (!is_array($cartProductIds)) {
                // Se for string, converter para array
                if (is_string($cartProductIds)) {
                    $cartProductIds = explode(',', $cartProductIds);
                } else {
                    $cartProductIds = [];
                }
            }

            $recommendations = $this->recommendationService->getRecommendationsForCart(
                $tenantId,
                $cartProductIds,
                $limit
            );

            return ApiResponseClass::sendResponse(
                $recommendations,
                'Recomendações carregadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar recomendações');
        }
    }
}

