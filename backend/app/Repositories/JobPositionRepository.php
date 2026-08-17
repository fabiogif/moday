<?php

namespace App\Repositories;

use App\Models\JobPosition;
use App\Repositories\Contracts\JobPositionRepositoryInterface;

class JobPositionRepository implements JobPositionRepositoryInterface
{
    public function __construct(private readonly JobPosition $entity) {}

    public function getAllByTenant(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();
    }

    public function getActiveByTenant(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function getById(int $id)
    {
        return $this->entity->find($id);
    }

    public function getByUuid(string $uuid)
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $position = $this->getById($id);
        if (!$position) {
            return null;
        }

        $position->update($data);

        return $position->fresh();
    }

    public function delete(int $id)
    {
        $position = $this->getById($id);

        return $position ? $position->delete() : false;
    }

    public function checkDuplicateName(int $tenantId, string $name, ?int $excludeId = null): bool
    {
        $query = $this->entity
            ->where('tenant_id', $tenantId)
            ->where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function hasAssignedUsers(int $id): bool
    {
        return $this->entity->find($id)?->users()->exists() ?? false;
    }
}
