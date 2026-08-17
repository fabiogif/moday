<?php

namespace App\Repositories\Contracts;

interface SalesGoalRepositoryInterface
{
    public function findAllForTenant(int $tenantId, array $filters = []);
    public function findForTenant(int $tenantId, int $id, array $with = []);
    public function findByUuidAndTenant(int $tenantId, string $uuid, array $with = []);
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getActiveGoalsForSeller(int $tenantId, int $userId);
    public function getActiveGoalsMatchingOrder(int $tenantId, int $userId, ?int $productId);
}
