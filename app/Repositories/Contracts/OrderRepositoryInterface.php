<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Repositories\Contracts\PaginateRepositoryInterface;

interface OrderRepositoryInterface
{
    public function createNewOrder(string $identify,
                                   float $total,
                                   string $status,
                                   int $tenantId,
                                   string $comment = null,
                                   $clientId = null,
                                   $tableId = null,
                                   array $deliveryData = [],
                                   $paymentMethodId = null,
                                   ?int $orderStatusId = null
    );

    public function getOrderByIdentify(string $identify):Order|null;

    public function registerProductsOrder(int $orderId, array $products);

    public function getOrdersByClientId(int $clientId): PaginateRepositoryInterface;

    public function paginateByTenant(int $tenantId, int $page, int $perPage, ?string $status = null): PaginateRepositoryInterface;

    public function updateOrder(string $identify, array $data): Order;

    public function delete(string $identify): bool;

    public function findRecentOrderByClientInfo(int $tenantId, ?string $cpf = null, ?string $phone = null): Order|null;

    public function archiveOrder(Order $order): Order;

    public function getOpenOrdersByTable(int $tenantId, string $tableUuid): array;

    public function getTodayOrders(int $tenantId): array;

    /**
     * Obter produtos frequentemente pedidos juntos com os produtos fornecidos
     * 
     * @param int $tenantId
     * @param array $productIds IDs internos dos produtos
     * @param int $limit Limite de resultados
     * @return array Array de produtos com frequência
     */
    public function getFrequentlyBoughtTogether(int $tenantId, array $productIds, int $limit = 6): array;

    /**
     * Obter produtos mais vendidos
     * 
     * @param int $tenantId
     * @param int $limit Limite de resultados
     * @param int|null $days Número de dias para filtrar (null = sem filtro de data)
     * @return array Array de produtos com total vendido
     */
    public function getBestSellers(int $tenantId, int $limit = 6, ?int $days = 30): array;

    public function identifyExists(string $identify): bool;

    public function getOrdersByClientIdWithRelations(int $clientId): \Illuminate\Support\Collection;

    public function findLastDeliveryOrderByClientId(int $clientId, int $tenantId): ?Order;

}
