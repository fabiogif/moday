<?php

namespace App\Services\SalesGoal;

use App\Repositories\Contracts\SalesGoalRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesGoalService
{
    public function __construct(
        private readonly SalesGoalRepositoryInterface $salesGoalRepository,
        private readonly CacheService $cacheService
    ) {}

    public function listByTenant(int $tenantId, array $filters = [])
    {
        return $this->cacheService->getSalesGoalList($tenantId, $filters, function () use ($tenantId, $filters) {
            return $this->salesGoalRepository->findAllForTenant($tenantId, $filters);
        });
    }

    public function find(int $tenantId, int $id, array $with = [])
    {
        return $this->salesGoalRepository->findForTenant($tenantId, $id, $with);
    }

    public function findByUuid(int $tenantId, string $uuid, array $with = [])
    {
        return $this->salesGoalRepository->findByUuidAndTenant($tenantId, $uuid, $with);
    }

    public function create(array $data, int $tenantId, int $userId)
    {
        DB::beginTransaction();
        try {
            $data['tenant_id'] = $tenantId;
            $data['created_by'] = $userId;
            $data['status'] = 'active';
            $data['current_value'] = 0;
            $data['completion_percent'] = 0;

            $goal = $this->salesGoalRepository->create($data);

            DB::commit();
            Log::info('Meta de venda criada', ['goal_id' => $goal->id, 'tenant_id' => $tenantId]);

            $this->cacheService->invalidateSalesGoalCache($tenantId);

            return $goal->load(['targetUser', 'targetProfile', 'targetProduct', 'createdByUser']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar meta de venda: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $tenantId, int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $goal = $this->salesGoalRepository->findForTenant($tenantId, $id);
            if (!$goal) {
                return null;
            }

            $updated = $this->salesGoalRepository->update($goal->id, $data);

            DB::commit();
            Log::info('Meta de venda atualizada', ['goal_id' => $goal->id, 'tenant_id' => $tenantId]);

            $this->cacheService->invalidateSalesGoalCache($tenantId);

            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar meta de venda: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $tenantId, int $id)
    {
        DB::beginTransaction();
        try {
            $goal = $this->salesGoalRepository->findForTenant($tenantId, $id);
            if (!$goal) {
                throw new \InvalidArgumentException('Meta não encontrada');
            }

            $this->salesGoalRepository->delete($goal->id);

            DB::commit();
            Log::info('Meta de venda excluída', ['goal_id' => $goal->id, 'tenant_id' => $tenantId]);

            $this->cacheService->invalidateSalesGoalCache($tenantId);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir meta de venda: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getMyGoals(int $tenantId, int $userId)
    {
        return $this->salesGoalRepository->getActiveGoalsForSeller($tenantId, $userId);
    }
}
