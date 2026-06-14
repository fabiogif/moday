<?php

namespace App\Repositories\Contracts;

interface ExpenseRepositoryInterface
{
    public function getAllByTenant(int $tenantId, array $filters = []);
    public function getById(int $id);
    public function getByUuid(string $uuid);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getByPeriod(int $tenantId, string $startDate, string $endDate);
    public function getTotalByPeriod(int $tenantId, string $startDate, string $endDate);
    public function getByCategory(int $tenantId, int $categoryId);
    public function getBySupplier(int $tenantId, int $supplierId);
    public function getPendingExpenses(int $tenantId);
}

