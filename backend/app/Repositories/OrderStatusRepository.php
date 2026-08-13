<?php

namespace App\Repositories;

use App\Models\OrderStatus;
use App\Repositories\Contracts\OrderStatusRepositoryInterface;
use Illuminate\Support\Collection;

class OrderStatusRepository implements OrderStatusRepositoryInterface
{
    public function __construct(
        protected OrderStatus $model
    ) {}

    public function getAllByTenant(int $tenantId, bool $activeOnly = false): Collection
    {
        $query = $this->model->forTenant($tenantId)->ordered();
        
        if ($activeOnly) {
            $query->active();
        }
        
        return $query->get();
    }

    public function getByUuid(string $uuid): ?OrderStatus
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function create(array $data): OrderStatus
    {
        return $this->model->create($data);
    }

    public function update(string $uuid, array $data): OrderStatus
    {
        $status = $this->getByUuid($uuid);
        
        if (!$status) {
            throw new \Exception('Status não encontrado');
        }
        
        $status->update($data);
        
        return $status->fresh();
    }

    public function delete(string $uuid): bool
    {
        $status = $this->getByUuid($uuid);
        
        if (!$status) {
            return false;
        }
        
        if (!$status->canBeDeleted()) {
            throw new \Exception('Não é possível deletar um status com pedidos associados');
        }
        
        return $status->delete();
    }

    public function updateOrder(int $tenantId, array $statusOrder): bool
    {
        foreach ($statusOrder as $index => $uuid) {
            $this->model->where('uuid', $uuid)
                ->where('tenant_id', $tenantId)
                ->update(['order_position' => $index + 1]);
        }
        
        return true;
    }

    public function getInitialStatus(int $tenantId): ?OrderStatus
    {
        return $this->model->forTenant($tenantId)
            ->where('is_initial', true)
            ->active()
            ->first();
    }

    public function getFinalStatuses(int $tenantId): Collection
    {
        return $this->model->forTenant($tenantId)
            ->where('is_final', true)
            ->active()
            ->get();
    }

    public function getFirstByOrder(int $tenantId): ?OrderStatus
    {
        return $this->model->forTenant($tenantId)
            ->active()
            ->ordered()
            ->first();
    }

    public function getByPosition(int $tenantId, int $position): ?OrderStatus
    {
        return $this->model->forTenant($tenantId)
            ->where('order_position', $position)
            ->first();
    }

    public function getByName(int $tenantId, string $name): ?OrderStatus
    {
        // Primeiro tenta busca exata
        $status = $this->model->forTenant($tenantId)
            ->where('name', $name)
            ->first();
        
        if ($status) {
            return $status;
        }
        
        // Se não encontrar, tenta busca case-insensitive
        return $this->model->forTenant($tenantId)
            ->whereRaw('LOWER(name) = LOWER(?)', [$name])
            ->first();
    }

    public function getById(int $id): ?OrderStatus
    {
        return $this->model->find($id);
    }
}

