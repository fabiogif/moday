<?php

namespace App\Services;

use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly PermissionRepositoryInterface $permissions
    ) {}

    public function getRolesForCurrentTenant()
    {
        $user = Auth::user();

        if (!$user || !$user->tenant_id) {
            return null;
        }

        return $this->roles->getRolesByTenant((int) $user->tenant_id);
    }

    public function createRoleForCurrentTenant(array $data): mixed
    {
        $user = Auth::user();

        if (!$user || !$user->tenant_id) {
            return null;
        }

        $data['tenant_id'] = $user->tenant_id;

        return $this->roles->create($data);
    }

    public function findRoleForCurrentTenant(int $roleId): mixed
    {
        $user = Auth::user();

        if (!$user || !$user->tenant_id) {
            return null;
        }

        return $this->roles->findForTenant($roleId, (int) $user->tenant_id);
    }

    public function updateRoleForCurrentTenant(int $roleId, array $data): mixed
    {
        $role = $this->findRoleForCurrentTenant($roleId);
        if (!$role) {
            return null;
        }

        $role->update($data);

        return $role->fresh(['permissions']);
    }

    public function deleteRoleForCurrentTenant(int $roleId): array
    {
        $role = $this->findRoleForCurrentTenant($roleId);
        if (!$role) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        $usersCount = $this->roles->countUsers($roleId);
        if ($usersCount > 0) {
            return ['success' => false, 'reason' => 'in_use', 'users_count' => $usersCount];
        }

        return [
            'success' => $this->roles->deleteRole($roleId),
        ];
    }

    public function getRoleStatsForCurrentTenant(): ?array
    {
        $user = Auth::user();

        if (!$user || !$user->tenant_id) {
            return null;
        }

        return $this->roles->getStatsForTenant((int) $user->tenant_id);
    }

    public function getRolePermissionsForCurrentTenant(int $roleId): mixed
    {
        if (!$this->findRoleForCurrentTenant($roleId)) {
            return null;
        }

        return $this->roles->getPermissions($roleId);
    }

    public function attachPermissionToRoleForCurrentTenant(int $roleId, int $permissionId): mixed
    {
        $role = $this->findRoleForCurrentTenant($roleId);
        $user = Auth::user();

        if (!$role || !$user || !$user->tenant_id) {
            return null;
        }

        $permission = $this->permissions->getPermissionById($permissionId);
        if (!$permission || $permission->tenant_id !== $user->tenant_id) {
            return null;
        }

        return $this->roles->attachPermission($roleId, $permissionId);
    }

    public function detachPermissionFromRoleForCurrentTenant(int $roleId, int $permissionId): mixed
    {
        $role = $this->findRoleForCurrentTenant($roleId);
        $user = Auth::user();

        if (!$role || !$user || !$user->tenant_id) {
            return null;
        }

        $permission = $this->permissions->getPermissionById($permissionId);
        if (!$permission || $permission->tenant_id !== $user->tenant_id) {
            return null;
        }

        return $this->roles->detachPermission($roleId, $permissionId);
    }

    public function syncPermissionsForRoleForCurrentTenant(int $roleId, array $permissionIds): mixed
    {
        $role = $this->findRoleForCurrentTenant($roleId);
        $user = Auth::user();

        if (!$role || !$user || !$user->tenant_id) {
            return null;
        }

        $validCount = 0;
        foreach ($permissionIds as $permissionId) {
            $permission = $this->permissions->getPermissionById($permissionId);
            if ($permission && $permission->tenant_id === $user->tenant_id) {
                $validCount++;
            }
        }

        if ($validCount !== count($permissionIds)) {
            return null;
        }

        return $this->roles->syncPermissions($roleId, $permissionIds);
    }
}
