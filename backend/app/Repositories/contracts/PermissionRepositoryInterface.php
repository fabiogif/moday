<?php

namespace App\Repositories\contracts;

interface PermissionRepositoryInterface extends BaseRepositoryInterface
{
    public function createPermission(array $data);
    public function getPermissionsByTenant($tenantId, $filters = [], $perPage = 15);
    public function getPermissionByUuid($uuid);
    public function updatePermission($id, array $data);
    public function deletePermission($id);
    public function getPermissionById($id);
}