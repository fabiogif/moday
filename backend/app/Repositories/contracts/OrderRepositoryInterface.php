<?php

namespace App\Repositories\contracts;

use App\Models\Order;
use App\Repositories\contracts\PaginateRepositoryInterface;

interface OrderRepositoryInterface
{
    public function createNewOrder(string $identify,
                                   float $total,
                                   string $status,
                                   int $tenantId,
                                   string $comment = null,
                                   $clientId = null,
                                   $tableId = null,
                                   array $deliveryData = []
    );

    public function getOrderByIdentify(string $identify):Order|null;

    public function registerProductsOrder(int $orderId, array $products);

    public function getOrdersByClientId(int $clientId): PaginateRepositoryInterface;

    public function paginateByTenant(int $tenantId, int $page, int $perPage, ?string $status = null): PaginateRepositoryInterface;

    /**
     * Pedidos para o quadro Kanban: ativos (Em Preparo/Pronto) + terminais recentes.
     *
     * @return \Illuminate\Support\Collection<int, Order>
     */
    public function getBoardByTenant(int $tenantId, int $terminalDays = 7);

    public function updateOrder(string $identify, array $data): Order;

}
