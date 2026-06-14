<?php

namespace App\Repositories\Contracts;

interface SupplierRepositoryInterface
{
    public function getAllByTenant(int $tenantId);
    public function getById(int $id);
    public function getByUuid(string $uuid);
    public function getByDocument(string $document);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function hasExpenses(int $id): bool;
}

