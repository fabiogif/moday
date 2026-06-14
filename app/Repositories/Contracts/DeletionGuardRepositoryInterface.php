<?php

namespace App\Repositories\Contracts;

interface DeletionGuardRepositoryInterface
{
    public function getPreparingOrderIdentifiesForProduct(int $productId): array;

    public function getActiveProductNamesForCategory(string $categoryUuid, ?int $tenantId = null): array;

    public function getPreparingOrderIdentifiesForClient(int $clientId): array;

    public function getPreparingOrderIdentifiesForTable(string $tableUuid): array;

    public function getActiveOrderIdentifiesForEntity(mixed $entidadeId, string $tipoEntidade, ?int $tenantId = null): array;
}
