<?php

namespace App\Repositories;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    protected Model $entity;

    public function __construct()
    {
        $this->entity = new Role();
    }

    public function getRolesByTenant(int $tenantId)
    {
        return $this->entity->where('tenant_id', $tenantId)
            ->with('permissions')
            ->orderBy('name')
            ->get();
    }

    public function getRoleById(int $id)
    {
        return $this->entity->with('permissions')->find($id);
    }

    public function deleteRole(int $id): bool
    {
        $role = $this->entity->find($id);

        if (!$role) {
            return false;
        }

        return (bool) $role->delete();
    }

    public function findForTenant(int $roleId, int $tenantId): mixed
    {
        return $this->entity->where('id', $roleId)
            ->where('tenant_id', $tenantId)
            ->with('permissions')
            ->first();
    }

    public function countUsers(int $roleId): int
    {
        $role = $this->entity->find($roleId);

        return $role ? $role->users()->count() : 0;
    }

    public function getStatsForTenant(int $tenantId): array
    {
        $base = $this->entity->where('tenant_id', $tenantId);

        return [
            'total_roles' => (clone $base)->count(),
            'recent_roles' => (clone $base)->where('created_at', '>=', now()->subDays(30))->count(),
            'admin_roles' => (clone $base)->where(function ($query) {
                $query->where('slug', 'like', '%admin%')
                    ->orWhere('slug', 'like', '%manager%')
                    ->orWhere('name', 'like', '%admin%')
                    ->orWhere('name', 'like', '%gerente%');
            })->count(),
            'user_roles' => (clone $base)->where(function ($query) {
                $query->where('slug', 'like', '%user%')
                    ->orWhere('slug', 'like', '%client%')
                    ->orWhere('name', 'like', '%user%')
                    ->orWhere('name', 'like', '%cliente%');
            })->count(),
        ];
    }

    public function detachPermission(int $roleId, int $permissionId): mixed
    {
        $role = $this->entity->find($roleId);
        if (!$role) {
            return null;
        }

        $role->permissions()->detach($permissionId);

        return $role->fresh(['permissions']);
    }

    public function attachPermission(int $roleId, int $permissionId): mixed
    {
        $role = $this->entity->find($roleId);
        if (!$role) {
            return null;
        }

        $role->permissions()->syncWithoutDetaching([$permissionId]);

        return $role->fresh(['permissions']);
    }

    public function syncPermissions(int $roleId, array $permissionIds): mixed
    {
        $role = $this->entity->find($roleId);
        if (!$role) {
            return null;
        }

        $role->permissions()->sync($permissionIds);

        return $role->fresh(['permissions']);
    }

    public function getPermissions(int $roleId): mixed
    {
        $role = $this->entity->find($roleId);

        return $role?->permissions()->get();
    }
}


