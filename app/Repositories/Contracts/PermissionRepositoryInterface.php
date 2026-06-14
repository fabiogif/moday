<?php

namespace App\Repositories\Contracts;

interface PermissionRepositoryInterface extends BaseRepositoryInterface
{
    public function createPermission(array $data);
    public function getPermissionsByTenant($tenantId, $filters = [], $perPage = 15);
    public function getPermissionByUuid($uuid);
    public function updatePermission($id, array $data);
    public function deletePermission($id);
    public function getPermissionById($id);

    public function findForTenant(int $permissionId, int $tenantId): ?\App\Models\Permission;

    public function countByIdsForTenant(array $ids, int $tenantId): int;
}