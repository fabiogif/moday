<?php

namespace App\Repositories;

use App\Models\Profile;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(protected Model $entity = new User())
    {
    }

    public function createUser(array $data)
    {
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

    public function countActiveUsersByTenant(int $tenantId): int
    {
        return $this->entity->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
    }

    public function paginateForTenant(int $tenantId, array $params): LengthAwarePaginator
    {
        $perPage = min($params['per_page'] ?? 15, 100);
        $filters = $params['filters'] ?? [];
        $search = $params['search'] ?? null;
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDirection = $params['sort_direction'] ?? 'desc';

        $query = $this->entity->with(['profiles', 'tenant', 'jobPosition:id,name'])
            ->where('tenant_id', $tenantId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    public function getStatsForTenant(int $tenantId): array
    {
        return [
            'total_users' => $this->entity->where('tenant_id', $tenantId)->count(),
            'active_users' => $this->entity->where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'pending_users' => $this->entity->where('tenant_id', $tenantId)->whereNull('email_verified_at')->count(),
            'inactive_users' => $this->entity->where('tenant_id', $tenantId)->where('is_active', false)->count(),
            'last_updated' => now()->toISOString(),
        ];
    }

    public function findProfileByIdAndTenant(int $profileId, int $tenantId): ?Profile
    {
        return Profile::where('id', $profileId)->where('tenant_id', $tenantId)->first();
    }

    public function syncProfiles(User $user, array $profileIds): void
    {
        $user->profiles()->sync($profileIds);
    }

    public function attachProfile(User $user, int $profileId): void
    {
        $user->profiles()->syncWithoutDetaching([$profileId]);
    }

    public function getUserPermissions(User $user): mixed
    {
        return $user->profiles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function loadRelations(User $user, array $relations): User
    {
        return $user->load($relations);
    }
}