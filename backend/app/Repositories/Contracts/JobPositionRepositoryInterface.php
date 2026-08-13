<?php

namespace App\Repositories\Contracts;

interface JobPositionRepositoryInterface
{
    public function getAllByTenant(int $tenantId);

    public function getActiveByTenant(int $tenantId);

    public function getById(int $id);

    public function getByUuid(string $uuid);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);

    public function checkDuplicateName(int $tenantId, string $name, ?int $excludeId = null): bool;

    public function hasAssignedUsers(int $id): bool;
}
