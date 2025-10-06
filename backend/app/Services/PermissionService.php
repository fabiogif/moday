<?php

namespace App\Services;

use App\Repositories\contracts\PermissionRepositoryInterface;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

readonly class PermissionService
{
    public function __construct(
        protected PermissionRepositoryInterface $permissionRepositoryInterface,
        protected CacheService $cacheService
    ) {
    }

    /**
     * Create a new permission.
     */
    public function createPermission(array $data, int $tenantId)
    {
        $data['tenant_id'] = $tenantId;
        
        $permission = $this->permissionRepositoryInterface->createPermission($data);
        
        // Invalidar cache
        $this->cacheService->invalidatePermissionCache($tenantId);
        
        return $permission;
    }

    /**
     * Get permissions by tenant with filters
     */
    public function getPermissionsByTenant(int $tenantId, array $filters = [], int $perPage = 15)
    {
        return $this->permissionRepositoryInterface->getPermissionsByTenant($tenantId, $filters, $perPage);
    }

    /**
     * Get all permissions by tenant without pagination
     */
    public function getAllPermissionsByTenant(int $tenantId, array $filters = [])
    {
        $query = Permission::where('tenant_id', $tenantId);
        
        // Apply filters
        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        
        if (isset($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }
        
        if (isset($filters['resource'])) {
            $query->where('resource', 'like', '%' . $filters['resource'] . '%');
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get permission by UUID
     */
    public function getPermissionByUuid(string $uuid, int $tenantId)
    {
        $permission = $this->permissionRepositoryInterface->getPermissionByUuid($uuid);
        
        // Verificar se pertence ao tenant
        if ($permission && $permission->tenant_id !== $tenantId) {
            return null;
        }
        
        return $permission;
    }

    /**
     * Get permission by ID
     */
    public function getPermissionById(string $id, int $tenantId)
    {
        $permission = Permission::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();
        
        return $permission;
    }

    /**
     * Update a permission.
     */
    public function updatePermission(string $uuid, array $data, int $tenantId)
    {
        $permission = $this->getPermissionByUuid($uuid, $tenantId);
        
        if (!$permission) {
            return null;
        }
        
        $updatedPermission = $this->permissionRepositoryInterface->updatePermission($permission->id, $data);
        
        // Invalidar cache
        $this->cacheService->invalidatePermissionCache($tenantId);
        
        return $updatedPermission;
    }

    /**
     * Update a permission by ID.
     */
    public function updatePermissionById(string $id, array $data, int $tenantId)
    {
        $permission = $this->getPermissionById($id, $tenantId);
        
        if (!$permission) {
            return null;
        }
        
        $permission->update($data);
        
        // Invalidar cache
        $this->cacheService->invalidatePermissionCache($tenantId);
        
        return $permission->fresh();
    }

    /**
     * Delete a permission.
     */
    public function deletePermission(string $uuid, int $tenantId)
    {
        $permission = $this->getPermissionByUuid($uuid, $tenantId);
        
        if (!$permission) {
            return false;
        }
        
        $deleted = $this->permissionRepositoryInterface->deletePermission($permission->id);
        
        // Invalidar cache
        if ($deleted) {
            $this->cacheService->invalidatePermissionCache($tenantId);
        }
        
        return $deleted;
    }

    /**
     * Delete a permission by ID.
     */
    public function deletePermissionById(string $id, int $tenantId)
    {
        $permission = $this->getPermissionById($id, $tenantId);
        
        if (!$permission) {
            return false;
        }
        
        $deleted = $permission->delete();
        
        // Invalidar cache
        if ($deleted) {
            $this->cacheService->invalidatePermissionCache($tenantId);
        }
        
        return $deleted;
    }

    /**
     * Create a new role.
     */
    public function createRole(array $data): Role
    {
        return Role::create($data);
    }

    /**
     * Update a role.
     */
    public function updateRole(Role $role, array $data): Role
    {
        $role->update($data);
        return $role->fresh();
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role): bool
    {
        return $role->delete();
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissionsToRole(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync($permissionIds);
    }

    /**
     * Remove permissions from a role.
     */
    public function removePermissionsFromRole(Role $role, array $permissionIds): void
    {
        $role->permissions()->detach($permissionIds);
    }

    /**
     * Assign roles to a user.
     */
    public function assignRolesToUser(User $user, array $roleIds): void
    {
        $user->roles()->sync($roleIds);
    }

    /**
     * Remove roles from a user.
     */
    public function removeRolesFromUser(User $user, array $roleIds): void
    {
        $user->roles()->detach($roleIds);
    }

    /**
     * Assign permissions directly to a user.
     */
    public function assignPermissionsToUser(User $user, array $permissionIds): void
    {
        $user->permissions()->sync($permissionIds);
    }

    /**
     * Remove permissions directly from a user.
     */
    public function removePermissionsFromUser(User $user, array $permissionIds): void
    {
        $user->permissions()->detach($permissionIds);
    }

    /**
     * Get all permissions for a user (direct + through roles).
     */
    public function getUserPermissions(User $user): Collection
    {
        return $user->getAllPermissions();
    }

    /**
     * Get all roles for a user.
     */
    public function getUserRoles(User $user): Collection
    {
        return $user->roles;
    }

    /**
     * Get all permissions for a role.
     */
    public function getRolePermissions(Role $role): Collection
    {
        return $role->permissions;
    }

    /**
     * Check if user has permission.
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission);
    }

    /**
     * Check if user has role.
     */
    public function userHasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }

    /**
     * Get permissions by module.
     */
    public function getPermissionsByModule(string $module): Collection
    {
        return Permission::module($module)->active()->get();
    }

    /**
     * Get roles by tenant.
     */
    public function getRolesByTenant(int $tenantId): Collection
    {
        return Role::forTenant($tenantId)->active()->get();
    }

    /**
     * Create default permissions for a module.
     */
    public function createModulePermissions(string $module, array $actions = ['create', 'read', 'update', 'delete']): Collection
    {
        $permissions = collect();

        foreach ($actions as $action) {
            $permission = Permission::create([
                'name' => ucfirst($action) . ' ' . ucfirst($module),
                'slug' => strtolower($module) . '.' . $action,
                'description' => "Permission to {$action} {$module}",
                'module' => $module,
                'action' => $action,
                'resource' => $module,
                'is_active' => true,
            ]);

            $permissions->push($permission);
        }

        return $permissions;
    }

    /**
     * Create default roles for a tenant.
     */
    public function createDefaultRoles(int $tenantId): Collection
    {
        $roles = collect();

        // Super Admin
        $superAdmin = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Full system access',
            'level' => 1,
            'is_active' => true,
            'tenant_id' => $tenantId,
        ]);
        $roles->push($superAdmin);

        // Admin
        $admin = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrative access',
            'level' => 2,
            'is_active' => true,
            'tenant_id' => $tenantId,
        ]);
        $roles->push($admin);

        // Manager
        $manager = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Management access',
            'level' => 3,
            'is_active' => true,
            'tenant_id' => $tenantId,
        ]);
        $roles->push($manager);

        // User
        $user = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Standard user access',
            'level' => 4,
            'is_active' => true,
            'tenant_id' => $tenantId,
        ]);
        $roles->push($user);

        return $roles;
    }

    /**
     * Assign all permissions to super admin role.
     */
    public function assignAllPermissionsToSuperAdmin(Role $superAdminRole): void
    {
        $allPermissions = Permission::active()->pluck('id')->toArray();
        $superAdminRole->permissions()->sync($allPermissions);
    }

    /**
     * Get permission statistics.
     */
    public function getPermissionStats(): array
    {
        return [
            'total_permissions' => Permission::count(),
            'active_permissions' => Permission::active()->count(),
            'total_roles' => Role::count(),
            'active_roles' => Role::active()->count(),
            'permissions_by_module' => Permission::active()
                ->selectRaw('module, COUNT(*) as count')
                ->groupBy('module')
                ->pluck('count', 'module')
                ->toArray(),
        ];
    }
}
