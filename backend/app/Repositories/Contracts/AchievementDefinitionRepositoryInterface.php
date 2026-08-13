<?php

namespace App\Repositories\Contracts;

interface AchievementDefinitionRepositoryInterface
{
    public function findAllForTenant(int $tenantId);
    public function findForTenant(int $tenantId, int $id);
    public function getActiveForTenant(int $tenantId);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
