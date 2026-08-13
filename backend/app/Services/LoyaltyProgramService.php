<?php

namespace App\Services;

use App\Repositories\Contracts\LoyaltyProgramRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyProgramService
{
    public function __construct(
        private readonly LoyaltyProgramRepositoryInterface $programRepository
    ) {}

    public function getProgramByTenant(int $tenantId)
    {
        return $this->programRepository->findByTenant($tenantId);
    }

    public function getActiveProgramByTenant(int $tenantId)
    {
        return $this->programRepository->getActiveByTenant($tenantId);
    }

    public function createProgram(array $data, int $tenantId)
    {
        DB::beginTransaction();
        try {
            // Desativar programa anterior se existir
            $existing = $this->programRepository->findByTenant($tenantId);
            if ($existing) {
                $this->programRepository->update($existing->id, ['is_active' => false]);
            }

            $data['tenant_id'] = $tenantId;
            $data['is_active'] = $data['is_active'] ?? true;

            $program = $this->programRepository->create($data);

            DB::commit();
            Log::info('Programa de fidelidade criado', ['program_id' => $program->id, 'tenant_id' => $tenantId]);

            return $program;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar programa: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateProgram(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $program = $this->programRepository->update($id, $data);

            DB::commit();
            Log::info('Programa atualizado', ['program_id' => $id]);

            return $program;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar programa: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteProgram(int $id)
    {
        DB::beginTransaction();
        try {
            $result = $this->programRepository->delete($id);

            DB::commit();
            Log::info('Programa excluído', ['program_id' => $id]);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir programa: ' . $e->getMessage());
            throw $e;
        }
    }
}

