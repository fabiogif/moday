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

            // Mesmo plano: reativa se a conta não estiver ativa (ex.: trial expirado)
            if ($oldPlanId === $newPlanId) {
                if (!$tenant->hasActiveSubscription()) {
                    if ($newPlan->isFree()) {
                        $tenant->activateFreePlan($newPlan->name);
                        $tenant->update(['mrr' => 0]);
                    } else {
                        $tenant->activateSubscription($newPlan->name);
                        $tenant->update(['mrr' => $newPlan->price]);
                    }

                    DB::commit();

                    Log::info('Plano reativado', [
                        'tenant_id' => $tenantId,
                        'plan_id' => $newPlanId,
                        'plan_free' => $newPlan->isFree(),
                    ]);

                    return [
                        'success' => true,
                        'message' => $newPlan->isFree()
                            ? 'Plano gratuito reativado com sucesso.'
                            : 'Assinatura reativada com sucesso.',
                        'migration' => null,
                        'tenant' => $tenant->fresh(['plan']),
                    ];
                }

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
            ]);

            if ($newPlan->isFree()) {
                // Plano gratuito permanente: libera acesso sem cobrança / Mercado Pago
                $tenant->activateFreePlan($newPlan->name);
                $tenant->update(['mrr' => 0]);
            } else {
                // Admin atribuiu plano pago: libera acesso (mesmo padrão do fluxo de pagamento)
                $tenant->activateSubscription($newPlan->name);
                $tenant->update(['mrr' => $newPlan->price]);
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
