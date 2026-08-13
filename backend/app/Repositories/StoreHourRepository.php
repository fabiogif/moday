<?php

namespace App\Repositories;

use App\Models\StoreHour;
use App\Repositories\Contracts\StoreHourRepositoryInterface;

class StoreHourRepository implements StoreHourRepositoryInterface
{
    protected StoreHour $entity;

    public function __construct(StoreHour $storeHour)
    {
        $this->entity = $storeHour;
    }

    public function getAllByTenant(int $tenantId, array $filters = [])
    {
        $query = $this->entity
            ->where('tenant_id', $tenantId);

        // Usar isset() ao invés de !empty() porque 0 (Domingo) é considerado "empty" em PHP
        if (isset($filters['day_of_week']) && $filters['day_of_week'] !== null && $filters['day_of_week'] !== '') {
            $query->where('day_of_week', $filters['day_of_week']);
        }

        if (!empty($filters['delivery_type'])) {
            $query->whereIn('delivery_type', [$filters['delivery_type'], 'both']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('day_of_week')->orderBy('start_time')->get();
    }

    public function getById(int $id)
    {
        return $this->entity->find($id);
    }

    public function getByUuid(string $uuid)
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $storeHour = $this->getById($id);
        if ($storeHour) {
            $storeHour->update($data);
            return $storeHour->fresh();
        }
        return null;
    }

    public function delete(int $id)
    {
        $storeHour = $this->getById($id);
        if ($storeHour) {
            return $storeHour->delete();
        }
        return false;
    }

    public function getByDayOfWeek(int $tenantId, int $dayOfWeek)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();
    }

    public function getByDeliveryType(int $tenantId, string $deliveryType)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->whereIn('delivery_type', [$deliveryType, 'both'])
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    public function getActiveHours(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    public function checkOverlap(int $tenantId, int $dayOfWeek, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        return StoreHour::hasOverlap($tenantId, $dayOfWeek, $startTime, $endTime, $excludeId);
    }

    public function isAlwaysOpen(int $tenantId): bool
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('is_always_open', true)
            ->where('is_active', true)
            ->exists();
    }
}

