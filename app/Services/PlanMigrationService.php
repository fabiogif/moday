<?php

namespace App\Services;

use App\Events\PlanConfirmed;
use App\Repositories\Contracts\PlanMigrationRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

readonly class PlanMigrationService
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepository,
        private PlanRepositoryInterface $planRepository,
        private PlanMigrationRepositoryInterface $planMigrationRepository,
    ) {}
    /**
     * Migra um tenant para um novo plano
     */
    public function migratePlan(int $tenantId, int $newPlanId, ?string $notes = null): array
    {
        DB::beginTransaction();
        
        try {
            $tenant = $this->tenantRepository->getById($tenantId);
            if (!$tenant) {
                throw new \Exception('Tenant não encontrado.');
            }

            $tenant->loadMissing('plan');

            $newPlan = $this->planRepository->getById($newPlanId);
            if (!$newPlan) {
                throw new \Exception('Plano não encontrado.');
            }

            // Validar se o plano está ativo
            if (!$newPlan->is_active) {
                throw new \Exception('O plano selecionado não está ativo.');
            }

            // Obter plano atual
            $oldPlan = $tenant->plan;
            $oldPlanId = $oldPlan ? $oldPlan->id : null;

            // Verificar se não é o mesmo plano
            if ($oldPlanId === $newPlanId) {
                throw new \Exception('O tenant já está neste plano.');
            }

            // Registrar migração no histórico
            $migration = $this->planMigrationRepository->createRecord([
                'tenant_id' => $tenantId,
                'from_plan_id' => $oldPlanId,
                'to_plan_id' => $newPlanId,
                'status' => 'completed',
                'migrated_at' => now(),
                'notes' => $notes,
            ]);

            // Atualizar tenant
            $tenant->update([
                'plan_id' => $newPlanId,
                'subscription_plan' => $newPlan->name,
            ]);

            // Atualizar MRR se necessário (futuro: calcular baseado no preço do plano)
            // Por enquanto, manter o MRR atual ou calcular baseado no preço do plano
            if ($newPlan->price > 0) {
                $tenant->update([
                    'mrr' => $newPlan->price,
                ]);
            }

            DB::commit();

            Log::info('Migração de plano realizada', [
                'tenant_id' => $tenantId,
                'from_plan_id' => $oldPlanId,
                'to_plan_id' => $newPlanId,
                'migration_id' => $migration->id,
            ]);

            // Disparar evento de plano confirmado para envio de e-mail
            event(new PlanConfirmed(
                $tenant->fresh(['plan']),
                $newPlan,
                $oldPlan,
                $migration
            ));

            return [
                'success' => true,
                'message' => 'Plano migrado com sucesso.',
                'migration' => $migration,
                'tenant' => $tenant->fresh(['plan']),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao migrar plano', [
                'tenant_id' => $tenantId,
                'new_plan_id' => $newPlanId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtém histórico de migrações de um tenant
     */
    public function getMigrationHistory(int $tenantId, ?int $limit = 10): array
    {
        return $this->planMigrationRepository->getHistoryForTenant($tenantId, $limit);
    }
}
