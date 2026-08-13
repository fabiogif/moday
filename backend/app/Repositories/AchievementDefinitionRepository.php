<?php

namespace App\Repositories;

use App\Models\AchievementDefinition;
use App\Repositories\Contracts\AchievementDefinitionRepositoryInterface;

class AchievementDefinitionRepository implements AchievementDefinitionRepositoryInterface
{
    protected AchievementDefinition $entity;

    public function __construct(AchievementDefinition $achievementDefinition)
    {
        $this->entity = $achievementDefinition;
    }

    public function findAllForTenant(int $tenantId)
    {
        return $this->entity
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
            })
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function findForTenant(int $tenantId, int $id)
    {
        return $this->entity
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
            })
            ->where('id', $id)
            ->first();
    }

    public function getActiveForTenant(int $tenantId)
    {
        return $this->entity
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
            })
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $definition = $this->entity->find($id);
        if ($definition) {
            $definition->update($data);
            return $definition->fresh();
        }
        return null;
    }

    public function delete(int $id)
    {
        $definition = $this->entity->find($id);
        if ($definition) {
            return $definition->delete();
        }
        return false;
    }
}
