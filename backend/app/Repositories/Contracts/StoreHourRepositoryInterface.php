<?php

namespace App\Repositories\Contracts;

interface StoreHourRepositoryInterface
{
    public function getAllByTenant(int $tenantId, array $filters = []);
    public function getById(int $id);
    public function getByUuid(string $uuid);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getByDayOfWeek(int $tenantId, int $dayOfWeek);
    public function getByDeliveryType(int $tenantId, string $deliveryType);
    public function getActiveHours(int $tenantId);
    public function checkOverlap(int $tenantId, int $dayOfWeek, string $startTime, string $endTime, ?int $excludeId = null): bool;
    public function isAlwaysOpen(int $tenantId): bool;
}

