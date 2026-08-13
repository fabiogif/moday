<?php

namespace App\Services;

use App\Repositories\Contracts\DeletionGuardRepositoryInterface;

class DeletionGuardService
{
    public function __construct(
        private readonly DeletionGuardRepositoryInterface $deletionGuardRepository,
    ) {}

    public function checkProductHasPreparingOrders(int $productId): array
    {
        $orders = $this->deletionGuardRepository->getPreparingOrderIdentifiesForProduct($productId);

        return [
            'blocked' => !empty($orders),
            'orders' => $orders,
        ];
    }

    public function checkCategoryHasActiveProducts(string $categoryUuid, ?int $tenantId = null): array
    {
        $products = $this->deletionGuardRepository->getActiveProductNamesForCategory($categoryUuid, $tenantId);

        return [
            'blocked' => !empty($products),
            'products' => $products,
        ];
    }

    public function checkClientHasPreparingOrders(int $clientId): array
    {
        $orders = $this->deletionGuardRepository->getPreparingOrderIdentifiesForClient($clientId);

        return [
            'blocked' => !empty($orders),
            'orders' => $orders,
        ];
    }

    public function checkTableHasPreparingOrders(string $tableUuid): array
    {
        $orders = $this->deletionGuardRepository->getPreparingOrderIdentifiesForTable($tableUuid);

        return [
            'blocked' => !empty($orders),
            'orders' => $orders,
        ];
    }

    public function verificarVinculoPedidoAtivo(mixed $entidadeId, string $tipoEntidade, ?int $tenantId = null): array
    {
        $orders = $this->deletionGuardRepository->getActiveOrderIdentifiesForEntity($entidadeId, $tipoEntidade, $tenantId);

        return [
            'blocked' => !empty($orders),
            'orders' => $orders,
        ];
    }
}
