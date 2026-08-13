<?php

namespace App\Repositories\Contracts;

interface LoyaltyProgramRepositoryInterface
{
    public function findByTenant(int $tenantId);
    public function getActiveByTenant(int $tenantId);
    public function getById(int $id);
    public function getByUuid(string $uuid);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}

