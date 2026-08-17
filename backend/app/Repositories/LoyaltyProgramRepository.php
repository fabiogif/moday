<?php

namespace App\Repositories;

use App\Models\LoyaltyProgram;
use App\Repositories\Contracts\LoyaltyProgramRepositoryInterface;

class LoyaltyProgramRepository implements LoyaltyProgramRepositoryInterface
{
    protected LoyaltyProgram $entity;

    public function __construct(LoyaltyProgram $loyaltyProgram)
    {
        $this->entity = $loyaltyProgram;
    }

    public function findByTenant(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->with('rewards')
            ->first();
    }

    public function getActiveByTenant(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('rewards')
            ->first();
    }

    public function getById(int $id)
    {
        return $this->entity->with('rewards')->find($id);
    }

    public function getByUuid(string $uuid)
    {
        return $this->entity->with('rewards')->where('uuid', $uuid)->first();
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $program = $this->getById($id);
        if ($program) {
            $program->update($data);
            return $program->fresh(['rewards']);
        }
        return null;
    }

    public function delete(int $id)
    {
        $program = $this->getById($id);
        if ($program) {
            return $program->delete();
        }
        return false;
    }
}

