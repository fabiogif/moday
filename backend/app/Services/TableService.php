<?php

namespace App\Services;

use App\Repositories\contracts\PaginateRepositoryInterface;
use App\Repositories\contracts\TableRepositoryInterface;
use App\Repositories\contracts\TenantRepositoryInterface;

readonly class TableService
{

    public function __construct(
        protected TableRepositoryInterface $tableRepositoryInterface,
        protected TenantRepositoryInterface $tenantRepositoryInterface,
        protected CacheService $cacheService
    )
    {

    }

    public function store(array $data)
    {
        return $this->tableRepositoryInterface->store($data);
    }

    public function update(array $data, int $id)
    {
        return $this->tableRepositoryInterface->update($data, $id);
    }

    public function delete(string $identify)
    {
        return $this->tableRepositoryInterface->delete($identify);
    }

    public function getTablesByTenant(string $uuid)
    {
        $tenant = $this->tenantRepositoryInterface->getTenantByUuid($uuid);
        return $this->cacheService->getTableList($tenant->id, function () use ($tenant) {
            return $this->tableRepositoryInterface->getTablesByTenantId($tenant->id);
        });
    }

    public  function getTablesByIdentify(string $idenfity)
    {
        return $this->tableRepositoryInterface->getTablesByIdentify($idenfity);
    }

    public function getTableByUuid(string $uuid)
    {
        return $this->tableRepositoryInterface->getTableByUuid($uuid);
    }

    public function paginate(int $page, int $totalPerPage, string $filter):PaginateRepositoryInterface
    {
        return $this->tableRepositoryInterface->paginate(page: $page, totalPrePage: $totalPerPage, filter:  $filter);
    }

    public function getStats(int $tenantId): array
    {
        return $this->tableRepositoryInterface->getStats($tenantId);
    }

}
