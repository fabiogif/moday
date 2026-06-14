<?php

namespace App\Repositories\Contracts;

use App\Models\OrderStatus;
use Illuminate\Support\Collection;

interface OrderStatusRepositoryInterface
{
    public function getAllByTenant(int $tenantId, bool $activeOnly = false): Collection;
    
    public function getByUuid(string $uuid): ?OrderStatus;
    
    public function create(array $data): OrderStatus;
    
    public function update(string $uuid, array $data): OrderStatus;
    
    public function delete(string $uuid): bool;
    
    public function updateOrder(int $tenantId, array $statusOrder): bool;
    
    public function getInitialStatus(int $tenantId): ?OrderStatus;
    
    public function getFinalStatuses(int $tenantId): Collection;
    
    public function getFirstByOrder(int $tenantId): ?OrderStatus;
    
    public function getByPosition(int $tenantId, int $position): ?OrderStatus;
    
    public function getByName(int $tenantId, string $name): ?OrderStatus;
    
    public function getById(int $id): ?OrderStatus;
}

