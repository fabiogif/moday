<?php

namespace App\Repositories\Contracts;

interface AccountReceivableRepositoryInterface
{
    public function getAllByTenant(int $tenantId, array $filters = []);
    public function getById(int $id);
    public function getByUuid(string $uuid);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function markAsReceived(int $id, array $data);
    public function getOverdue(int $tenantId);
    public function getTotalExpected(int $tenantId, string $startDate, string $endDate);
    public function getTotalReceived(int $tenantId, string $startDate, string $endDate);
}

