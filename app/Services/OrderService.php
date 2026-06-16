<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Tenant;
use App\Repositories\Contracts\{OrderRepositoryInterface,
    OrderStatusRepositoryInterface,
    ProductRepositoryInterface,
    TableRepositoryInterface,
    TenantRepositoryInterface,
    ClientRepositoryInterface};
use App\Repositories\Contracts\PaginateRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

readonly class OrderService
{

    public function __construct(
        protected OrderRepositoryInterface $orderRepositoryInterface,
        protected TenantRepositoryInterface $tenantRepositoryInterface,
        protected TableRepositoryInterface $tableRepositoryInterface,
        protected ProductRepositoryInterface $productRepositoryInterface,
        protected ClientRepositoryInterface $clientRepositoryInterface,
        protected CacheService $cacheService,
        protected OrderStatusRepositoryInterface $orderStatusRepositoryInterface,
    )
    {}

    public function createNewOrder(array $order)
    {
        $productsOrder = $this->getProductsByOrder($order['products']?? []);

        $identify = $this->getIdentifyOrder();
        $total = $this->getTotalOrder($productsOrder);
        $tenantId =  $this->getTenantIdByOrder($order['token_company']);
        $initialStatus = $this->resolveInitialStatus($tenantId);
        $statusName = $initialStatus?->name ?? 'Em Preparo';
        $comment = isset($order['comment']) ? $order['comment'] : '';
        $clientId = $this->getClientIdByOrder($order['client_id'] ?? null);
        $tableId = $this->getTableIdByOrder($order['table'] ?? '');

        // Delivery fields
        $isDelivery = $order['is_delivery'] ?? false;
        $useClientAddress = $order['use_client_address'] ?? false;
        
        // Se usar endereço do cliente, buscar dados do cliente
        $deliveryData = [];
        if ($isDelivery) {
            if ($useClientAddress && $clientId) {
                $client = $this->clientRepositoryInterface->getClientById($clientId);
                if ($client && $client->hasCompleteAddress()) {
                    $deliveryData = [
                        'delivery_address' => $client->address,
                        'delivery_city' => $client->city,
                        'delivery_state' => $client->state,
                        'delivery_zip_code' => $client->zip_code,
                        'delivery_neighborhood' => $client->neighborhood,
                        'delivery_number' => $client->number,
                        'delivery_complement' => $client->complement,
                    ];
                }
            } else {
                // Usar endereço fornecido no pedido
                $deliveryData = [
                    'delivery_address' => $order['delivery_address'] ?? null,
                    'delivery_city' => $order['delivery_city'] ?? null,
                    'delivery_state' => $order['delivery_state'] ?? null,
                    'delivery_zip_code' => $order['delivery_zip_code'] ?? null,
                    'delivery_neighborhood' => $order['delivery_neighborhood'] ?? null,
                    'delivery_number' => $order['delivery_number'] ?? null,
                    'delivery_complement' => $order['delivery_complement'] ?? null,
                ];
            }
            
            $deliveryData['is_delivery'] = true;
            $deliveryData['use_client_address'] = $useClientAddress;
            $deliveryData['delivery_notes'] = $order['delivery_notes'] ?? null;
        }

       // Processar dados de troco se necessário
       $changeData = [];
       if (isset($order['precisa_troco']) && $order['precisa_troco']) {
           $changeData['precisa_troco'] = true;
           if (isset($order['valor_recebido']) && $order['valor_recebido'] > 0) {
               $changeData['valor_recebido'] = (float) $order['valor_recebido'];
           }
       } else {
           $changeData['precisa_troco'] = false;
           $changeData['valor_recebido'] = null;
       }
       
       // Processar split_payments se presente
       $paymentMethodId = null;
       if (!empty($order['split_payments']) && is_array($order['split_payments'])) {
           // Quando há split_payments, salvar como array (será convertido para JSON automaticamente)
           $changeData['split_payments'] = $order['split_payments'];
           
           // Opcionalmente, usar o primeiro método de pagamento como principal para compatibilidade
           // Mas apenas se não houver payment_method_id explícito
           if (empty($order['payment_method_id']) && !empty($order['split_payments'][0]['payment_method_id'])) {
               $paymentMethodId = $order['split_payments'][0]['payment_method_id'];
           }
       } else {
           // Usar payment_method_id quando não há split_payments
           $paymentMethodId = $order['payment_method_id'] ?? null;
       }
       
       // Mesclar dados de troco e split_payments com deliveryData
       $allOrderData = array_merge($deliveryData, $changeData);
       
       $order = $this->orderRepositoryInterface->createNewOrder(
           identify: $identify,
           total: $total,
           status:  $statusName,
           tenantId: $tenantId ,
           comment:  $comment,
           clientId:  $clientId,
           tableId:   $tableId,
           deliveryData: $allOrderData,
           paymentMethodId: $paymentMethodId && Schema::hasColumn('orders', 'payment_method_id') ? $paymentMethodId : null,
           orderStatusId: $initialStatus?->id
        );
       $this->orderRepositoryInterface->registerProductsOrder($order->id, $productsOrder);

       // Invalidate cache after creating order
       $this->cacheService->invalidateOrderCache($tenantId);

       // Dispatch broadcasting event for new order (graceful fallback if broadcasting fails)
       try {
           \App\Events\OrderCreated::dispatch($order);
           
           // Dispatch dashboard metrics update
           $this->dispatchDashboardUpdate($tenantId);
       } catch (\Exception $e) {
           Log::warning('Failed to broadcast OrderCreated event: ' . $e->getMessage());
       }

       return $order;
    }

    private function getIdentifyOrder(int $qtyCaraceters = 8): string
    {
        $smallLetters = str_shuffle('abcdefghijklmnopqrstuvwxyz');

        $numbers = (((date('Ymd') / 12) * 24) + mt_rand(800, 9999));
        $numbers .= 1234567890;

        $characters = $smallLetters.$numbers;

        $identify = substr(str_shuffle($characters), 0, $qtyCaraceters);

        if ($this->orderRepositoryInterface->getOrderByIdentify($identify)) {
            $this->getIdentifyOrder($qtyCaraceters + 1);
        }

        return $identify;
    }

    private function getProductsByOrder(array $productsOrder): array
    {
        $products = [];

        foreach ($productsOrder as $item) {
            $product = $this->productRepositoryInterface->getByUuid($item['identify']);
            // Usar o preço fornecido no item (pode ter sido alterado) ou o preço do produto
            $price = isset($item['price']) ? (float) $item['price'] : $product->price;
            array_push($products, [
                'id' => $product->id,
                'qty'=> $item['qty'],
                'price' => $price
            ]);
        }
        return $products;
    }
    private function getTableIdByOrder(string $uuid = ''): string
    {
        if ($uuid) {
            $table = $this->tableRepositoryInterface->getTableByUuid($uuid);
            return $table->id;
        }

        return '';
    }
    private function getTotalOrder(array $productsOrder): float
    {
        $total = 0;
        foreach ($productsOrder as $item) {
            $total += $item['qty'] * $item['price'];
        }
        return (float)$total;
    }

    private function getTenantIdByOrder(string $uuid):Tenant|null|int
    {

       $tenant = $this->tenantRepositoryInterface->getTenantByUuid($uuid);
       return $tenant->id;
    }


    private function getClientIdByOrder($clientUuid = null)
    {
        if ($clientUuid) {
            $client = $this->clientRepositoryInterface->getClientByUuid($clientUuid);
            return $client ? $client->id : null;
        }
        
        // Se não foi fornecido client_id, pode ser um pedido sem cliente específico
        return null;
    }
    public function ordersByClient()
    {
        $clientId = Auth::check() ? Auth::user()->id : null;
        if (!$clientId) {
            return collect(); // Return empty collection if no authenticated client
        }
        return $this->orderRepositoryInterface->getOrdersByClientId($clientId);
    }

    public function getOrderByIdentify($identify):Order|null
    {
        return $this->orderRepositoryInterface->getOrderByIdentify($identify);
    }

    public function paginateByTenant(int $tenantId, int $page, int $perPage, ?string $status = null): PaginateRepositoryInterface
    {
        // Include pagination params in cache key to avoid conflicts between different requests
        $params = [
            'page' => $page,
            'per_page' => $perPage,
            'status' => $status
        ];
        
        return $this->cacheService->getOrderList($tenantId, function () use ($tenantId, $page, $perPage, $status) {
            return $this->orderRepositoryInterface->paginateByTenant($tenantId, $page, $perPage, $status);
        }, $params);
    }

    /**
     * Get order statistics with cache
     */
    public function getOrderStats(int $tenantId): array
    {
        return $this->cacheService->getOrderStats($tenantId, function () use ($tenantId) {
            return $this->calculateOrderStats($tenantId);
        });
    }

    /**
     * Calculate order statistics (without cache)
     */
    private function calculateOrderStats(int $tenantId): array
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $previousMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Get orders for current and previous months
        $currentMonthOrders = \App\Models\Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->get();

        $previousMonthOrders = \App\Models\Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->get();

        // Total orders
        $currentTotalOrders = $currentMonthOrders->count();
        $previousTotalOrders = $previousMonthOrders->count();

        // Total revenue
        $currentRevenue = $currentMonthOrders->sum('total');
        $previousRevenue = $previousMonthOrders->sum('total');

        // Average order value
        $currentAvgOrderValue = $currentTotalOrders > 0 ? $currentRevenue / $currentTotalOrders : 0;
        $previousAvgOrderValue = $previousTotalOrders > 0 ? $previousRevenue / $previousTotalOrders : 0;

        // Orders by status (current month)
        $currentOrdersByStatus = $currentMonthOrders->groupBy('status')
            ->map(function ($orders) {
                return $orders->count();
            })
            ->toArray();
        
        // Orders by status (previous month)
        $previousOrdersByStatus = $previousMonthOrders->groupBy('status')
            ->map(function ($orders) {
                return $orders->count();
            })
            ->toArray();

        // Calcular estatísticas específicas por status
        $inPreparoCurrent = $currentOrdersByStatus['Em Preparo'] ?? 0;
        $inPreparoPrevious = $previousOrdersByStatus['Em Preparo'] ?? 0;
        
        $prontoCurrent = $currentOrdersByStatus['Pronto'] ?? 0;
        $prontoPrevious = $previousOrdersByStatus['Pronto'] ?? 0;
        
        $deliveredCurrent = $currentOrdersByStatus['Entregue'] ?? 0;
        $deliveredPrevious = $previousOrdersByStatus['Entregue'] ?? 0;
        
        $canceledCurrent = $currentOrdersByStatus['Cancelado'] ?? 0;
        $canceledPrevious = $previousOrdersByStatus['Cancelado'] ?? 0;

        // Calculate growth percentages
        $ordersGrowth = $previousTotalOrders > 0 
            ? round((($currentTotalOrders - $previousTotalOrders) / $previousTotalOrders) * 100, 1)
            : ($currentTotalOrders > 0 ? 100 : 0);

        $revenueGrowth = $previousRevenue > 0 
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : ($currentRevenue > 0 ? 100 : 0);

        $avgOrderValueGrowth = $previousAvgOrderValue > 0 
            ? round((($currentAvgOrderValue - $previousAvgOrderValue) / $previousAvgOrderValue) * 100, 1)
            : ($currentAvgOrderValue > 0 ? 100 : 0);
        
        $inPreparoGrowth = $inPreparoPrevious > 0
            ? round((($inPreparoCurrent - $inPreparoPrevious) / $inPreparoPrevious) * 100, 1)
            : ($inPreparoCurrent > 0 ? 100 : 0);
        
        $prontoGrowth = $prontoPrevious > 0
            ? round((($prontoCurrent - $prontoPrevious) / $prontoPrevious) * 100, 1)
            : ($prontoCurrent > 0 ? 100 : 0);
        
        $deliveredGrowth = $deliveredPrevious > 0
            ? round((($deliveredCurrent - $deliveredPrevious) / $deliveredPrevious) * 100, 1)
            : ($deliveredCurrent > 0 ? 100 : 0);

        return [
            'total_orders' => [
                'current' => $currentTotalOrders,
                'previous' => $previousTotalOrders,
                'growth' => $ordersGrowth
            ],
            'total_revenue' => [
                'current' => round($currentRevenue, 2),
                'previous' => round($previousRevenue, 2),
                'growth' => $revenueGrowth
            ],
            'average_order_value' => [
                'current' => round($currentAvgOrderValue, 2),
                'previous' => round($previousAvgOrderValue, 2),
                'growth' => $avgOrderValueGrowth
            ],
            'in_preparo_orders' => [
                'current' => $inPreparoCurrent,
                'previous' => $inPreparoPrevious,
                'growth' => $inPreparoGrowth
            ],
            'pronto_orders' => [
                'current' => $prontoCurrent,
                'previous' => $prontoPrevious,
                'growth' => $prontoGrowth
            ],
            'delivered_orders' => [
                'current' => $deliveredCurrent,
                'previous' => $deliveredPrevious,
                'growth' => $deliveredGrowth
            ],
            'canceled_orders' => [
                'current' => $canceledCurrent,
                'previous' => $canceledPrevious,
                'growth' => $canceledPrevious > 0 
                    ? round((($canceledCurrent - $canceledPrevious) / $canceledPrevious) * 100, 1)
                    : ($canceledCurrent > 0 ? 100 : 0)
            ],
            'orders_by_status' => $currentOrdersByStatus
        ];
    }

    /**
     * Get cached order data
     */
    public function getCachedOrderData(int $tenantId, string $identifier)
    {
        return $this->cacheService->getOrderData($tenantId, $identifier, function () use ($tenantId, $identifier) {
            return $this->getOrderByIdentify($identifier);
        });
    }

    public function archiveOrder(string $identify): Order
    {
        $order = $this->orderRepositoryInterface->getOrderByIdentify($identify);

        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }

        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId || $order->tenant_id !== $tenantId) {
            throw new \Exception('Não autorizado a arquivar este pedido');
        }

        if ($order->status === Order::STATUS_ARCHIVED || $order->archived_at !== null) {
            throw new \DomainException('Pedido já está arquivado.');
        }

        $archivedOrder = $this->orderRepositoryInterface->archiveOrder($order);

        $this->cacheService->invalidateOrderCache($tenantId);
        $this->cacheService->invalidateOrderDataCache($tenantId);

        return $archivedOrder;
    }


    public function deleteOrder($identify): void
    {
        $order = $this->orderRepositoryInterface->getOrderByIdentify($identify);

        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }

        $tenantId = $order->tenant_id;

        $this->orderRepositoryInterface->delete($identify);

        // Invalidar cache
        $this->cacheService->invalidateOrderCache($tenantId);
        $this->cacheService->invalidateOrderDataCache($tenantId);
    }

    /**
     * Excluir múltiplos pedidos em massa
     * 
     * @param array $orderIdentifies Array de identifies dos pedidos
     * @return array Array com informações sobre o resultado da operação
     */
    public function bulkDeleteOrders(array $orderIdentifies): array
    {
        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId) {
            throw new \Exception('Usuário não possui tenant associado');
        }

        $deleted = [];
        $notFound = [];
        $unauthorized = [];
        $errors = [];

        foreach ($orderIdentifies as $identify) {
            try {
                $order = $this->orderRepositoryInterface->getOrderByIdentify($identify);
                
                if (!$order) {
                    $notFound[] = $identify;
                    continue;
                }

                // Verificar se o pedido pertence ao tenant do usuário
                if ($order->tenant_id !== $tenantId) {
                    $unauthorized[] = $identify;
                    continue;
                }

                // Excluir pedido
                $this->orderRepositoryInterface->delete($identify);
                $deleted[] = $identify;
            } catch (\Exception $e) {
                $errors[] = [
                    'identify' => $identify,
                    'error' => $e->getMessage()
                ];
                Log::error("Erro ao excluir pedido {$identify}: " . $e->getMessage());
            }
        }

        // Invalidar cache após exclusões
        if (!empty($deleted)) {
            $this->cacheService->invalidateOrderCache($tenantId);
            $this->cacheService->invalidateOrderDataCache($tenantId);
        }

        return [
            'deleted' => $deleted,
            'not_found' => $notFound,
            'unauthorized' => $unauthorized,
            'errors' => $errors,
            'total_requested' => count($orderIdentifies),
            'total_deleted' => count($deleted),
        ];
    }

    /**
     * Atualizar status de múltiplos pedidos em massa
     * 
     * @param array $orderIdentifies Array de identifies dos pedidos
     * @param string $status Nome do novo status
     * @return array Array com informações sobre o resultado da operação
     */
    public function bulkUpdateOrdersStatus(array $orderIdentifies, string $status): array
    {
        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId) {
            throw new \Exception('Usuário não possui tenant associado');
        }

        // Buscar o status no banco de dados
        $statusModel = $this->orderStatusRepositoryInterface->getByName($tenantId, $status);
        
        // Se não encontrar e o status solicitado for "Concluído", tentar "Entregue" como fallback
        if (!$statusModel && $status === 'Concluído') {
            $statusModel = $this->orderStatusRepositoryInterface->getByName($tenantId, 'Entregue');
        }
        
        // Se ainda não encontrar, buscar qualquer status final disponível
        if (!$statusModel && $status === 'Concluído') {
            $finalStatuses = $this->orderStatusRepositoryInterface->getFinalStatuses($tenantId);
            if ($finalStatuses->isNotEmpty()) {
                $statusModel = $finalStatuses->first();
            }
        }
        
        if (!$statusModel) {
            // Buscar todos os status disponíveis para fornecer mensagem mais informativa
            $availableStatuses = $this->orderStatusRepositoryInterface->getAllByTenant($tenantId, false);
            $availableStatusNames = $availableStatuses->pluck('name')->toArray();
            
            $message = "Status '{$status}' não encontrado para este tenant.";
            if (!empty($availableStatusNames)) {
                $message .= " Status disponíveis: " . implode(', ', $availableStatusNames);
            }
            
            throw new \Exception($message);
        }

        $updated = [];
        $notFound = [];
        $unauthorized = [];
        $finalStatus = [];
        $errors = [];

        foreach ($orderIdentifies as $identify) {
            try {
                $order = $this->orderRepositoryInterface->getOrderByIdentify($identify);
                
                if (!$order) {
                    $notFound[] = $identify;
                    continue;
                }

                // Verificar se o pedido pertence ao tenant do usuário
                if ($order->tenant_id !== $tenantId) {
                    $unauthorized[] = $identify;
                    continue;
                }

                // Verificar se o pedido possui status final - não pode ser editado
                if ($this->isOrderFinal($order, $tenantId)) {
                    $finalStatus[] = $identify;
                    continue;
                }

                // Atualizar status do pedido
                $order->status = $statusModel->name;
                $order->order_status_id = $statusModel->id;
                $order->save();

                $updated[] = $identify;
            } catch (\Exception $e) {
                $errors[] = [
                    'identify' => $identify,
                    'error' => $e->getMessage()
                ];
                Log::error("Erro ao atualizar status do pedido {$identify}: " . $e->getMessage());
            }
        }

        // Invalidar cache após atualizações
        if (!empty($updated)) {
            $this->cacheService->invalidateOrderCache($tenantId);
            $this->cacheService->invalidateOrderDataCache($tenantId);
        }

        return [
            'updated' => $updated,
            'not_found' => $notFound,
            'unauthorized' => $unauthorized,
            'final_status' => $finalStatus,
            'errors' => $errors,
            'total_requested' => count($orderIdentifies),
            'total_updated' => count($updated),
        ];
    }

    /**
     * Update order
     */
    public function updateOrder(string $identify, array $data): Order
    {
        $order = $this->orderRepositoryInterface->getOrderByIdentify($identify);
        
        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }

        // Verificar se o usuário pode atualizar este pedido
        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId || $order->tenant_id !== $tenantId) {
            throw new \Exception('Não autorizado a atualizar este pedido');
        }

        // Verificar se o pedido possui status final - não pode ser editado
        if ($this->isOrderFinal($order, $tenantId)) {
            throw new \DomainException('Este pedido está finalizado e não pode ser alterado.');
        }

        // Armazenar o status anterior para broadcasting
        $oldStatus = $order->status;

        // Preparar dados para atualização
        $updateData = [];

        $statusModel = null;
        $newStatusName = $data['status'] ?? null;

        if (isset($data['status_position'])) {
            $statusModel = $this->orderStatusRepositoryInterface->getByPosition($tenantId, (int) $data['status_position']);
            if (!$statusModel) {
                throw new \Exception('Status informado é inválido para este tenant');
            }
        }

        if (!$statusModel && isset($data['status_uuid'])) {
            $candidate = $this->orderStatusRepositoryInterface->getByUuid($data['status_uuid']);
            if ($candidate && $candidate->tenant_id === $tenantId) {
                $statusModel = $candidate;
            }
        }

        if (!$statusModel && isset($data['status'])) {
            $statusModel = $this->orderStatusRepositoryInterface->getByName($tenantId, $data['status']);
        }

        if ($statusModel) {
            $updateData['status'] = $statusModel->name;
            $updateData['order_status_id'] = $statusModel->id;
            $newStatusName = $statusModel->name;
        } elseif (isset($data['status'])) {
            $updateData['status'] = $data['status'];
            $updateData['order_status_id'] = null;
        }

        if (isset($data['comment'])) {
            $updateData['comment'] = $data['comment'];
        }

        if (isset($data['is_delivery'])) {
            $updateData['is_delivery'] = $data['is_delivery'];
        }

        if (isset($data['use_client_address'])) {
            $updateData['use_client_address'] = $data['use_client_address'];
        }

        // Delivery fields
        $deliveryFields = [
            'delivery_address',
            'delivery_city', 
            'delivery_state',
            'delivery_zip_code',
            'delivery_neighborhood',
            'delivery_number',
            'delivery_complement',
            'delivery_notes'
        ];

        foreach ($deliveryFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        // Se usar endereço do cliente e o endereço foi alterado, limpar campos de delivery
        if (isset($data['use_client_address']) && $data['use_client_address'] === true) {
            foreach ($deliveryFields as $field) {
                $updateData[$field] = null;
            }
        }

        // Se produtos foram fornecidos, atualizar produtos e recalcular total
        if (isset($data['products']) && is_array($data['products'])) {
            // Remover produtos antigos
            $order->products()->detach();
            
            // Preparar produtos para inserção
            $productsOrder = $this->getProductsByOrder($data['products']);
            
            // Recalcular total
            $newTotal = $this->getTotalOrder($data['products']);
            $updateData['total'] = $newTotal;
            
            // Registrar novos produtos
            $this->orderRepositoryInterface->registerProductsOrder($order->id, $productsOrder);
        }

        // Se payment_method_id foi fornecido, atualizar método de pagamento
        if (isset($data['payment_method_id'])) {
            $paymentMethod = \App\Models\PaymentMethod::where('uuid', $data['payment_method_id'])
                ->where('tenant_id', $tenantId)
                ->first();
            
            if ($paymentMethod) {
                // O campo payment_method_id armazena o UUID (conforme relacionamento)
                $updateData['payment_method_id'] = $paymentMethod->uuid;
            }
        }
        
        // Processar dados de troco
        if (isset($data['precisa_troco'])) {
            $updateData['precisa_troco'] = (bool) $data['precisa_troco'];
            if ($data['precisa_troco'] && isset($data['valor_recebido']) && $data['valor_recebido'] > 0) {
                $updateData['valor_recebido'] = (float) $data['valor_recebido'];
            } else {
                $updateData['valor_recebido'] = null;
            }
        }

        // Atualizar pedido
        $updatedOrder = $this->orderRepositoryInterface->updateOrder($identify, $updateData);

        // Invalidar cache relacionado
        $this->cacheService->invalidateOrderCache($tenantId);
        $this->cacheService->invalidateOrderDataCache($tenantId);

        // Dispatch broadcasting events (graceful fallback if broadcasting fails)
        try {
            if (isset($updateData['status']) && $newStatusName !== null && $oldStatus !== $newStatusName) {
                \App\Events\OrderStatusUpdated::dispatch($updatedOrder, $oldStatus, $newStatusName);
            } else {
                \App\Events\OrderUpdated::dispatch($updatedOrder);
            }
            
            // Dispatch dashboard metrics update
            $this->dispatchDashboardUpdate($tenantId);
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast order update event: ' . $e->getMessage());
        }

        return $updatedOrder;
    }

    /**
     * Dispatch dashboard metrics update
     */
    private function dispatchDashboardUpdate(int $tenantId): void
    {
        try {
            $currentMonth = \Carbon\Carbon::now()->startOfMonth();
            
            $metrics = [
                'total_orders' => \App\Models\Order::where('tenant_id', $tenantId)
                    ->where('created_at', '>=', $currentMonth)
                    ->count(),
                'total_revenue' => \App\Models\Order::where('tenant_id', $tenantId)
                    ->where('created_at', '>=', $currentMonth)
                    ->sum('total'),
                'timestamp' => now()->toISOString()
            ];

            \App\Events\DashboardMetricsUpdated::dispatch($tenantId, $metrics);
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch dashboard update: ' . $e->getMessage());
        }
    }

    /**
     * Buscar pedidos por número (para autocomplete)
     */
    public function searchOrdersByNumber(int $tenantId, string $query): array
    {
        $orders = Order::where('tenant_id', $tenantId)
            ->where('identify', 'like', "%{$query}%")
            ->with(['client'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'identify' => $order->identify,
                'client_name' => $order->client?->name ?? 'Sem cliente',
                'total' => $order->total,
                'status' => $order->status,
                'created_at' => $order->created_at->format('d/m/Y'),
            ];
        })->toArray();
    }

    /**
     * Obter detalhes de um pedido para preencher formulário de contas
     */
    public function getOrderDetailsForAccount(int $orderId, int $tenantId): ?array
    {
        $order = Order::where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->with(['client', 'paymentMethod'])
            ->first();

        if (!$order) {
            return null;
        }

        return [
            'id' => $order->id,
            'identify' => $order->identify,
            'total' => $order->total,
            'client_id' => $order->client_id,
            'client_name' => $order->client?->name ?? null,
            'payment_method_id' => $order->payment_method_id,
            'payment_method_name' => $order->paymentMethod?->name ?? null,
            'status' => $order->status,
            'created_at' => $order->created_at->format('Y-m-d'),
        ];
    }

    /**
     * Consulta pública de pedidos por telefone
     * Retorna apenas informações não sensíveis dos últimos 10 pedidos
     */
    public function findOrderByClientInfo(int $tenantId, ?string $cpf = null, ?string $phone = null): ?array
    {
        if ($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
        }

        if (!$phone) {
            return null;
        }

        $orders = $this->orderRepositoryInterface->findRecentOrderByClientInfo($tenantId, null, $phone);

        if ($orders->isEmpty() || !$orders->first()->client) {
            return null;
        }

        $clientName = explode(' ', $orders->first()->client->name)[0] ?? '';

        $ordersData = $orders->map(function ($order) {
            $products = $order->products->map(function ($product) {
                return [
                    'name' => $product->name,
                    'quantity' => $product->pivot->qty ?? 1,
                    'price' => (float) ($product->pivot->price ?? $product->price),
                ];
            })->toArray();

            return [
                'order_identify' => $order->identify,
                'order_date' => $order->created_at->format('d/m/Y'),
                'order_time' => $order->created_at->format('H:i'),
                'status' => $order->status,
                'status_color' => $order->orderStatus?->color ?? null,
                'order_position' => $order->orderStatus?->order_position ?? 0,
                'is_final' => (bool) ($order->orderStatus?->is_final ?? false),
                'is_delivery' => (bool) ($order->is_delivery ?? false),
                'total' => (float) $order->total,
                'products' => $products,
                'payment_method' => $order->paymentMethod?->name ?? 'Não informada',
            ];
        })->toArray();

        return [
            'client_name' => $clientName,
            'orders' => $ordersData,
        ];
    }
    private function resolveInitialStatus(int $tenantId): ?\App\Models\OrderStatus
    {
        if (!$tenantId) {
            return null;
        }

        $initialStatus = $this->orderStatusRepositoryInterface->getInitialStatus($tenantId);
        if ($initialStatus && $initialStatus->is_active) {
            return $initialStatus;
        }

        $firstStatus = $this->orderStatusRepositoryInterface->getFirstByOrder($tenantId);
        if ($firstStatus && $firstStatus->is_active) {
            return $firstStatus;
        }

        return null;
    }

    public function getOpenOrdersByTable(int $tenantId, string $tableUuid): array
    {
        return $this->orderRepositoryInterface->getOpenOrdersByTable($tenantId, $tableUuid);
    }

    public function getTodayOrders(int $tenantId): array
    {
        return $this->orderRepositoryInterface->getTodayOrders($tenantId);
    }

    /**
     * Verificar se um pedido possui status final
     * 
     * @param Order $order
     * @param int $tenantId
     * @return bool
     */
    private function isOrderFinal(Order $order, int $tenantId): bool
    {
        // Verificar se o pedido tem order_status_id e se esse status é final
        if ($order->order_status_id) {
            $status = $this->orderStatusRepositoryInterface->getById($order->order_status_id);
            if ($status && $status->is_final) {
                return true;
            }
        }

        // Verificar pelo nome do status usando apenas os status finais do banco de dados
        $finalStatuses = $this->orderStatusRepositoryInterface->getFinalStatuses($tenantId);
        $finalStatusNames = $finalStatuses->pluck('name')->toArray();
        
        // Se encontrou status finais no banco, usar apenas esses
        if (!empty($finalStatusNames)) {
            return in_array($order->status, $finalStatusNames);
        }
        
        // Fallback apenas se não houver nenhum status final configurado no banco
        // (caso edge case de sistema novo ou sem configuração)
        // Usar apenas os status finais padrão do novo fluxo
        $defaultFinalStatuses = ['Entregue', 'Cancelado'];
        
        return in_array($order->status, $defaultFinalStatuses);
    }

    /**
     * Avançar status do pedido para o próximo do fluxo
     * 
     * @param string $identify
     * @return Order
     * @throws \Exception
     */
    public function advanceOrderStatus(string $identify): Order
    {
        $order = $this->orderRepositoryInterface->getOrderByIdentify($identify);
        
        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }

        // Verificar se o usuário pode atualizar este pedido
        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId || $order->tenant_id !== $tenantId) {
            throw new \Exception('Não autorizado a atualizar este pedido');
        }

        // Verificar se o pedido possui status final - não pode ser editado
        if ($this->isOrderFinal($order, $tenantId)) {
            throw new \DomainException('Este pedido está finalizado e não pode ter status alterado.');
        }

        $currentStatus = $this->getCurrentStatusModel($order, $tenantId);
        if (!$currentStatus) {
            throw new \Exception('Não foi possível identificar o status atual do pedido. Status do pedido: ' . ($order->status ?? 'não definido'));
        }

        $nextStatus = $this->getNextStatus($tenantId, $currentStatus, $order->is_delivery);
        
        if (!$nextStatus) {
            // Obter todos os status disponíveis para mensagem de erro mais informativa
            $allStatuses = $this->orderStatusRepositoryInterface->getAllByTenant($tenantId, false);
            $availableStatusNames = $allStatuses->pluck('name')->toArray();
            
            $normalizedCurrent = $this->normalizeStatusName($currentStatus->name);
            $flow = [
                'Pedido Recebido' => 'Confirmado',
                'Confirmado' => 'Em Preparação',
                'Em Preparação' => 'Pronto para Expedição',
                'Pronto para Expedição' => $order->is_delivery ? 'Aguardando Entregador' : 'Entregue',
                'Pronto' => $order->is_delivery ? 'Aguardando Entregador' : 'Entregue',
                'Aguardando Entregador' => 'Em Entrega',
                'Em Entrega' => 'Entregue',
            ];
            $expectedNextStatus = $flow[$normalizedCurrent] ?? $flow[$currentStatus->name] ?? null;
            
            throw new \Exception(
                "Não há próximo status disponível para este pedido. " .
                "Status atual: {$currentStatus->name}. " .
                "Próximo status esperado: {$expectedNextStatus}. " .
                "Status disponíveis no sistema: " . implode(', ', $availableStatusNames)
            );
        }

        // Atualizar status do pedido
        $order->status = $nextStatus->name;
        $order->order_status_id = $nextStatus->id;
        $order->save();

        // Invalidar cache
        $this->cacheService->invalidateOrderCache($tenantId);
        $this->cacheService->invalidateOrderDataCache($tenantId);

        return $order->fresh();
    }

    /**
     * Obter modelo de status atual do pedido
     * 
     * @param Order $order
     * @param int $tenantId
     * @return \App\Models\OrderStatus|null
     */
    private function getCurrentStatusModel(Order $order, int $tenantId): ?\App\Models\OrderStatus
    {
        // Se tem order_status_id, buscar por ID
        if ($order->order_status_id) {
            $status = $this->orderStatusRepositoryInterface->getById($order->order_status_id);
            if ($status) {
                return $status;
            }
        }

        // Se não, buscar por nome
        if ($order->status) {
            return $this->orderStatusRepositoryInterface->getByName($tenantId, $order->status);
        }

        return null;
    }

    /**
     * Obter próximo status do fluxo
     * 
     * @param int $tenantId
     * @param \App\Models\OrderStatus $currentStatus
     * @param bool $isDelivery
     * @return \App\Models\OrderStatus|null
     */
    private function getNextStatus(int $tenantId, \App\Models\OrderStatus $currentStatus, bool $isDelivery): ?\App\Models\OrderStatus
    {
        // Mapear fluxo de status baseado no início do nome (para lidar com variações como "Em Preparação / Cozinha")
        $flowPatterns = [
            'Pedido Recebido' => ['Confirmado', 'Em Preparação', 'Em Preparação / Cozinha'],
            'Confirmado' => ['Em Preparação', 'Em Preparação / Cozinha'],
            'Em Preparação' => ['Pronto para Expedição', 'Pronto', 'Pronto / Finalizado pela Cozinha'],
            'Pronto para Expedição' => $isDelivery
                ? ['Aguardando Entregador', 'Em Entrega', 'Em Entrega / Saiu para Entrega']
                : ['Entregue'],
            'Pronto' => $isDelivery
                ? ['Aguardando Entregador', 'Em Entrega', 'Em Entrega / Saiu para Entrega']
                : ['Entregue'],
            'Aguardando Entregador' => ['Em Entrega', 'Em Entrega / Saiu para Entrega'],
            'Em Entrega' => ['Entregue'],
        ];

        // Normalizar nome do status atual para busca (remover variações como "/ Cozinha")
        $currentStatusNormalized = $this->normalizeStatusName($currentStatus->name);
        
        // Determinar próximo status esperado
        $expectedNextStatuses = null;
        foreach ($flowPatterns as $pattern => $nextStatuses) {
            if ($this->matchesStatusPattern($currentStatusNormalized, $pattern)) {
                $expectedNextStatuses = $nextStatuses;
                break;
            }
        }
        
        if (!$expectedNextStatuses) {
            Log::warning("Não há próximo status mapeado para: {$currentStatus->name}", [
                'tenant_id' => $tenantId,
                'current_status' => $currentStatus->name,
                'normalized_status' => $currentStatusNormalized,
                'is_delivery' => $isDelivery
            ]);
            return null;
        }

        // Buscar próximo status - primeiro tentar busca exata, depois busca parcial
        $allStatuses = $this->orderStatusRepositoryInterface->getAllByTenant($tenantId, false);
        
        foreach ($expectedNextStatuses as $expectedName) {
            // Tentar busca exata primeiro
            $nextStatus = $allStatuses->first(function ($status) use ($expectedName) {
                return strtolower(trim($status->name)) === strtolower(trim($expectedName));
            });
            
            if ($nextStatus) {
                return $nextStatus;
            }
            
            // Se não encontrou exato, tentar busca parcial (começa com)
            $nextStatus = $allStatuses->first(function ($status) use ($expectedName) {
                $statusName = strtolower(trim($status->name));
                $expectedNameLower = strtolower(trim($expectedName));
                return strpos($statusName, $expectedNameLower) === 0 || strpos($expectedNameLower, $statusName) === 0;
            });
            
            if ($nextStatus) {
                return $nextStatus;
            }
        }
        
        // Se não encontrou nenhum, logar erro
        Log::warning("Próximo status não encontrado no banco de dados", [
            'tenant_id' => $tenantId,
            'current_status' => $currentStatus->name,
            'normalized_status' => $currentStatusNormalized,
            'expected_next_statuses' => $expectedNextStatuses,
            'is_delivery' => $isDelivery,
            'available_statuses' => $allStatuses->pluck('name')->toArray()
        ]);
        
        return null;
    }

    /**
     * Normalizar nome do status removendo variações comuns
     * 
     * @param string $statusName
     * @return string
     */
    private function normalizeStatusName(string $statusName): string
    {
        // Remover variações comuns como "/ Cozinha", "/ Finalizado pela Cozinha", etc.
        $normalized = trim($statusName);
        
        // Remover sufixos comuns após "/"
        if (strpos($normalized, '/') !== false) {
            $normalized = trim(explode('/', $normalized)[0]);
        }
        
        return $normalized;
    }

    /**
     * Verificar se um status normalizado corresponde a um padrão
     * 
     * @param string $normalizedStatus
     * @param string $pattern
     * @return bool
     */
    private function matchesStatusPattern(string $normalizedStatus, string $pattern): bool
    {
        return strtolower(trim($normalizedStatus)) === strtolower(trim($pattern));
    }
}
