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

    public function updateOrder(string $identify, array $data): Order;

}
