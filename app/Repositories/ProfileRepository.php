<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProfileRepository implements ProfileRepositoryInterface
{
    public function __construct(protected Model $entity = new Profile())
    {
    }

    public function listByTenant(int $tenantId, array $filters = []): Collection
    {
        $query = $this->entity->with(['permissions', 'tenant'])
            ->where('tenant_id', $tenantId);

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->get();
    }

    public function createForTenant(array $data, int $tenantId): Profile
    {
        $data['tenant_id'] = $tenantId;
        $permissions = $data['permissions'] ?? null;
        unset($data['permissions']);

        $profile = $this->entity->create($data);

        if ($permissions !== null) {
            $profile->permissions()->sync($permissions);
        }

        return $profile->load(['permissions', 'tenant']);
    }

    public function findForTenant(int $profileId, int $tenantId): ?Profile
    {
        return $this->entity->where('id', $profileId)->where('tenant_id', $tenantId)->first();
    }

    public function updateProfile(Profile $profile, array $data): Profile
    {
        $permissions = $data['permissions'] ?? null;
        unset($data['permissions']);

        $profile->update($data);

        if ($permissions !== null) {
            $profile->permissions()->sync($permissions);
        }

        return $profile->load(['permissions', 'tenant']);
    }

    public function deleteProfile(Profile $profile): bool
    {
        return (bool) $profile->delete();
    }

    public function countUsers(Profile $profile): int
    {
        return $profile->users()->count();
    }

    public function findByName(string $name): ?Profile
    {
        return $this->entity->where('name', $name)->first();
    }

    public function getPermissionsForProfile(int $profileId, int $tenantId): ?Collection
    {
        $profile = $this->findForTenant($profileId, $tenantId);

        return $profile?->permissions()->get();
    }

    public function getAvailablePermissionsForProfile(int $profileId, int $tenantId): ?Collection
    {
        $profile = $this->findForTenant($profileId, $tenantId);
        if (!$profile) {
            return null;
        }

        $assignedIds = $profile->permissions()->pluck('permissions.id');

        return Permission::where('tenant_id', $tenantId)
            ->whereNotIn('id', $assignedIds)
            ->get();
    }

    public function attachPermission(int $profileId, int $permissionId, int $tenantId): ?Profile
    {
        $profile = $this->findForTenant($profileId, $tenantId);
        if (!$profile) {
            return null;
        }

        $permission = Permission::where('id', $permissionId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$permission) {
            return null;
        }

        if (!$profile->permissions()->where('permission_id', $permission->id)->exists()) {
            $profile->permissions()->attach($permission->id);
        }

        return $profile->refresh();
    }

    public function detachPermission(int $profileId, int $permissionId, int $tenantId): ?Profile
    {
        $profile = $this->findForTenant($profileId, $tenantId);
        if (!$profile) {
            return null;
        }

        $permission = Permission::where('id', $permissionId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$permission) {
            return null;
        }

        if ($profile->permissions()->where('permission_id', $permission->id)->exists()) {
            $profile->permissions()->detach($permission->id);
        }

        return $profile->refresh();
    }

    public function syncPermissions(int $profileId, array $permissionIds, int $tenantId): ?Profile
    {
        $profile = $this->findForTenant($profileId, $tenantId);
        if (!$profile) {
            return null;
        }

        $profile->permissions()->sync($permissionIds);

        return $profile->refresh();
    }

    public function getProfilesForPermission(int $permissionId, int $tenantId): ?Collection
    {
        $permission = Permission::where('id', $permissionId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$permission) {
            return null;
        }

        return $permission->profiles()->get();
    }
}
