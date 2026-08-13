<?php

namespace App\Services;

use App\Repositories\Contracts\OrderStatusRepositoryInterface;
use App\Models\OrderStatus;
use Illuminate\Support\Collection;

class OrderStatusService
{
    public function __construct(
        protected OrderStatusRepositoryInterface $repository
    ) {}

    public function getAllStatuses(int $tenantId, bool $activeOnly = true): Collection
    {
        return $this->repository->getAllByTenant($tenantId, $activeOnly);
    }

    public function getStatusByUuid(string $uuid): ?array
    {
        $status = $this->repository->getByUuid($uuid);
        
        if (!$status) {
            return null;
        }
        
        return [
            'uuid' => $status->uuid,
            'name' => $status->name,
            'slug' => $status->slug,
            'description' => $status->description,
            'color' => $status->color,
            'icon' => $status->icon,
            'order_position' => $status->order_position,
            'is_initial' => $status->is_initial,
            'is_final' => $status->is_final,
            'is_active' => $status->is_active,
            'orders_count' => $status->orders()->count(),
            'can_delete' => $status->canBeDeleted(),
        ];
    }

    public function createStatus(int $tenantId, array $data): array
    {
        // Se for marcado como inicial, desmarcar outros
        if ($data['is_initial'] ?? false) {
            $this->removeInitialStatus($tenantId);
        }

        // Determinar próxima posição
        $maxPosition = OrderStatus::where('tenant_id', $tenantId)->max('order_position') ?? 0;

        $status = $this->repository->create(array_merge($data, [
            'tenant_id' => $tenantId,
            'order_position' => $maxPosition + 1,
        ]));

        return $this->getStatusByUuid($status->uuid);
    }

    public function updateStatus(string $uuid, array $data): array
    {
        $status = $this->repository->getByUuid($uuid);
        
        if (!$status) {
            throw new \Exception('Status não encontrado');
        }

        // Se for marcado como inicial, desmarcar outros
        if (isset($data['is_initial']) && $data['is_initial']) {
            $this->removeInitialStatus($status->tenant_id, $uuid);
        }

        $updatedStatus = $this->repository->update($uuid, $data);

        return $this->getStatusByUuid($updatedStatus->uuid);
    }

    public function deleteStatus(string $uuid): bool
    {
        return $this->repository->delete($uuid);
    }

    public function reorderStatuses(int $tenantId, array $statusOrder): bool
    {
        return $this->repository->updateOrder($tenantId, $statusOrder);
    }

    private function removeInitialStatus(int $tenantId, ?string $exceptUuid = null)
    {
        $query = OrderStatus::where('tenant_id', $tenantId)
            ->where('is_initial', true);
        
        if ($exceptUuid) {
            $query->where('uuid', '!=', $exceptUuid);
        }
        
        $query->update(['is_initial' => false]);
    }
}

