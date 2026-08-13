<?php

namespace App\Repositories\Contracts;

interface TableRepositoryInterface extends BaseRepositoryInterface
{
    public function getTablesByTenantUuid(string $uuid);
    public function getTablesByTenantId(int $idTenant);
    public function getTablesByIdentify(string $identify);
    public function getTableByUuid(string $uuid);
    public function getStats(int $tenantId): array;
}
