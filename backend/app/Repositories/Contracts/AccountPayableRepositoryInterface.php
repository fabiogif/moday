<?php

namespace App\Repositories\Contracts;

interface AccountPayableRepositoryInterface
{
    public function getAllByTenant(int $tenantId, array $filters = []);
    public function getById(int $id);
    public function getByUuid(string $uuid);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getOverdue(int $tenantId);
    public function getUpcoming(int $tenantId, int $days = 7);
    public function bulkPay(array $ids, array $paymentData);
    public function getTotalByPeriod(int $tenantId, string $startDate, string $endDate);
}

