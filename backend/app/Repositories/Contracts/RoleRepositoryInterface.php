<?php

namespace App\Repositories\Contracts;

interface RoleRepositoryInterface extends BaseRepositoryInterface
{
    public function getRolesByTenant(int $tenantId);

    public function getRoleById(int $id);

    public function deleteRole(int $id): bool;

    public function findForTenant(int $roleId, int $tenantId): mixed;

    public function countUsers(int $roleId): int;

    public function getStatsForTenant(int $tenantId): array;

    public function detachPermission(int $roleId, int $permissionId): mixed;

    public function attachPermission(int $roleId, int $permissionId): mixed;

    public function syncPermissions(int $roleId, array $permissionIds): mixed;

    public function getPermissions(int $roleId): mixed;
}


