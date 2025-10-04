<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(protected Model $entity = new User())
    {
    }

    public function createUser(array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        return $this->entity->create($data);
    }

    public function getUsersByTenant($tenantId, $filters = [], $perPage = 15)
    {
        $query = $this->entity->where('tenant_id', $tenantId);
        
        // Apply filters
        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        
        if (isset($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        return $query->with(['roles', 'permissions'])->paginate($perPage);
    }

    public function getUserByUuid($uuid)
    {
        return $this->entity->where('uuid', $uuid)->with(['roles', 'permissions'])->first();
    }

    public function getUserByEmail($email)
    {
        return $this->entity->where('email', $email)->first();
    }

    public function getUserById($id)
    {
        return $this->entity->with(['roles', 'permissions'])->find($id);
    }

    public function updateUser($id, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        
        $user = $this->entity->find($id);
        if (!$user) {
            return null;
        }
        
        $user->update($data);
        return $user->fresh(['roles', 'permissions']);
    }

    public function deleteUser($id)
    {
        $user = $this->entity->find($id);
        if (!$user) {
            return false;
        }
        
        return $user->delete();
    }
}