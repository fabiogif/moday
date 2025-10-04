<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Repositories\contracts\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class PermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    public function __construct(protected Model $entity = new Permission())
    {
    }

    public function createPermission(array $data)
    {
        return $this->entity->create($data);
    }

    public function getPermissionsByTenant($tenantId, $filters = [], $perPage = 15)
    {
        $query = $this->entity->where('tenant_id', $tenantId);
        
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
        
        return $query->paginate($perPage);
    }

    public function getPermissionByUuid($uuid)
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function getPermissionById($id)
    {
        return $this->entity->find($id);
    }

    public function updatePermission($id, array $data)
    {
        $permission = $this->entity->find($id);
        if (!$permission) {
            return null;
        }
        
        $permission->update($data);
        return $permission->fresh();
    }

    public function deletePermission($id)
    {
        $permission = $this->entity->find($id);
        if (!$permission) {
            return false;
        }
        
        return $permission->delete();
    }
}