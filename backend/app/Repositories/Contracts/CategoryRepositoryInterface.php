<?php

namespace App\Repositories\Contracts;

interface CategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUuid(string $identify);
    public function getByUuidAndTenant(string $identify, int $tenantId);
    public function paginateByTenant(int $page, int $totalPerPage, string $filter, int $tenantId): PaginateRepositoryInterface;
    public function updateByTenant(array $data, int $id, int $tenantId);
    public function deleteByTenant(string $identify, int $tenantId);
    public function getStats(int $tenantId): array;
}
