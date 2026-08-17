<?php

namespace App\Services;

use App\Models\JobPosition;
use App\Repositories\Contracts\JobPositionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobPositionService
{
    public const DEFAULT_POSITIONS = [
        ['name' => 'Vendedor', 'description' => 'Responsável por vendas, atendimento e agenda de visitas a clientes'],
        ['name' => 'Motorista', 'description' => 'Responsável por conduzir veículos de entrega'],
        ['name' => 'Entregador', 'description' => 'Realiza entregas aos clientes'],
        ['name' => 'Ajudante', 'description' => 'Apoio na carga, descarga e entregas'],
        ['name' => 'Administrativo', 'description' => 'Atividades administrativas e de escritório'],
    ];

    public function __construct(
        private readonly JobPositionRepositoryInterface $jobPositionRepository
    ) {}

    public function getAllPositions(int $tenantId, bool $activeOnly = false)
    {
        return $activeOnly
            ? $this->jobPositionRepository->getActiveByTenant($tenantId)
            : $this->jobPositionRepository->getAllByTenant($tenantId);
    }

    public function getPositionByUuid(string $uuid): ?JobPosition
    {
        return $this->jobPositionRepository->getByUuid($uuid);
    }

    public function createPosition(array $data, int $tenantId): JobPosition
    {
        return DB::transaction(function () use ($data, $tenantId) {
            if ($this->jobPositionRepository->checkDuplicateName($tenantId, $data['name'])) {
                throw new \InvalidArgumentException('Já existe um cargo com este nome.');
            }

            $data['tenant_id'] = $tenantId;
            $data['is_active'] = $data['is_active'] ?? true;

            $position = $this->jobPositionRepository->create($data);

            Log::info('Cargo criado', ['job_position_id' => $position->id, 'tenant_id' => $tenantId]);

            return $position;
        });
    }

    public function updatePosition(int $id, array $data): JobPosition
    {
        return DB::transaction(function () use ($id, $data) {
            $position = $this->jobPositionRepository->getById($id);
            if (!$position) {
                throw new \InvalidArgumentException('Cargo não encontrado.');
            }

            if (isset($data['name']) && $data['name'] !== $position->name) {
                if ($this->jobPositionRepository->checkDuplicateName($position->tenant_id, $data['name'], $id)) {
                    throw new \InvalidArgumentException('Já existe um cargo com este nome.');
                }
            }

            $updated = $this->jobPositionRepository->update($id, $data);
            if (!$updated) {
                throw new \InvalidArgumentException('Cargo não encontrado.');
            }

            return $updated;
        });
    }

    public function deletePosition(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $position = $this->jobPositionRepository->getById($id);
            if (!$position) {
                throw new \InvalidArgumentException('Cargo não encontrado.');
            }

            if ($this->jobPositionRepository->hasAssignedUsers($id)) {
                throw new \InvalidArgumentException('Não é possível excluir um cargo vinculado a funcionários.');
            }

            return (bool) $this->jobPositionRepository->delete($id);
        });
    }

    public function seedDefaultsForTenant(int $tenantId): void
    {
        foreach (self::DEFAULT_POSITIONS as $defaults) {
            $exists = $this->jobPositionRepository->checkDuplicateName($tenantId, $defaults['name']);
            if ($exists) {
                continue;
            }

            $this->jobPositionRepository->create([
                'tenant_id' => $tenantId,
                'name' => $defaults['name'],
                'description' => $defaults['description'],
                'is_active' => true,
            ]);
        }
    }
}
