<?php

namespace App\Repositories\Contracts;

use App\DTO\Integrations\Ifood\IfoodOrderDTO;
use App\Models\Integrations\Ifood\IfoodOrder;
use App\Repositories\Contracts\PaginateRepositoryInterface;

interface IfoodOrderRepositoryInterface
{
    public function findByExternalId(string $externalId): ?IfoodOrder;

    public function createOrUpdateFromDto(IfoodOrderDTO $dto, ?int $tenantId = null): IfoodOrder;

    public function attachItems(IfoodOrder $order, array $items): void;

    public function attachStatus(IfoodOrder $order, array $statusLog): void;

    public function linkInternalOrder(IfoodOrder $ifoodOrder, int $orderId): void;

    public function paginateByTenant(int $tenantId, int $perPage = 20): PaginateRepositoryInterface;
}

