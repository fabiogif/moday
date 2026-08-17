<?php

namespace App\Repositories\Contracts;

interface FinancialCategoryRepositoryInterface
{
    public function getAllByTenant(int $tenantId);
    public function getByType(int $tenantId, string $type);
    public function getById(int $id);
    public function getByUuid(string $uuid);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function checkDuplicateName(int $tenantId, string $name, string $type, ?int $excludeId = null);
}

