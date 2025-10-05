<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Tenant;
use App\Repositories\contracts\{OrderRepositoryInterface,
    ProductRepositoryInterface,
    TableRepositoryInterface,
    TenantRepositoryInterface,
    ClientRepositoryInterface};
use App\Repositories\contracts\PaginateRepositoryInterface;
use Carbon\Carbon;

readonly class OrderService
{

    public function __construct(
        protected OrderRepositoryInterface $orderRepositoryInterface,
        protected TenantRepositoryInterface $tenantRepositoryInterface,
        protected TableRepositoryInterface $tableRepositoryInterface,
        protected ProductRepositoryInterface $productRepositoryInterface,
        protected ClientRepositoryInterface $clientRepositoryInterface,
        protected CacheService $cacheService
    )
    {}

    public function createNewOrder(array $order)
    {
        $productsOrder = $this->getProductsByOrder($order['products']?? []);

        $identify = $this->getIdentifyOrder();
        $total = $this->getTotalOrder($productsOrder);
        $status = 'Em Preparo';
        $tenantId =  $this->getTenantIdByOrder($order['token_company']);
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

       $order = $this->orderRepositoryInterface->createNewOrder(
           identify: $identify,
           total: $total,
           status:  $status,
           tenantId: $tenantId ,
           comment:  $comment,
           clientId:  $clientId,
           tableId:   $tableId,
           deliveryData: $deliveryData
        );
       $this->orderRepositoryInterface->registerProductsOrder($order->id, $productsOrder);

       // Invalidate cache after creating order
       $this->cacheService->invalidateOrderCache($tenantId);

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
            array_push($products, [
                'id' => $product->id,
                'qty'=> $item['qty'],
                'price' => $product->price ] );
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
        $clientId = auth()->check() ? auth()->user()->id : null;
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
        return $this->cacheService->getOrderList($tenantId, function () use ($tenantId, $page, $perPage, $status) {
            return $this->orderRepositoryInterface->paginateByTenant($tenantId, $page, $perPage, $status);
        });
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


    public function deleteOrder($identify): void
    {
        $order = $this->orderRepository->findByIdentify($identify);

        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }

        $tenantId = $order->tenant_id;

        $this->orderRepository->delete($identify);

        // Invalidar cache
        $this->cacheService->invalidateOrderCache($tenantId);
        $this->cacheService->invalidateOrderDataCache($tenantId);
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
        $tenantId = auth()->user()?->tenant_id;
        if (!$tenantId || $order->tenant_id !== $tenantId) {
            throw new \Exception('Não autorizado a atualizar este pedido');
        }

        // Preparar dados para atualização
        $updateData = [];

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
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

        // Atualizar pedido
        $updatedOrder = $this->orderRepositoryInterface->updateOrder($identify, $updateData);

        // Invalidar cache relacionado
        $this->cacheService->invalidateOrderCache($tenantId);
        $this->cacheService->invalidateOrderDataCache($tenantId);

        return $updatedOrder;
    }

}
