<?php

namespace App\Services;

use App\Repositories\Contracts\AdminActionLogRepositoryInterface;
use App\Repositories\Contracts\PlanMigrationRepositoryInterface;
use Illuminate\Support\Facades\Log;

readonly class AdminPlanMigrationService
{
    public function __construct(
        private PlanMigrationService $planMigrationService,
        private PlanMigrationRepositoryInterface $planMigrationRepository,
        private AdminActionLogRepositoryInterface $actionLogRepository
    ) {}

    public function migrateTenant($admin, array $data, string $ip): array
    {
        $result = $this->planMigrationService->migratePlan(
            $data['tenant_id'],
            $data['plan_id'],
            $data['reason'] ?? null
        );

        $this->actionLogRepository->logAction(
            $admin,
            'migrate_tenant_plan',
            "Migrou empresa #{$data['tenant_id']} para o plano #{$data['plan_id']}",
            null,
            null,
            [
                'from_plan_id' => $result['migration']->from_plan_id,
                'to_plan_id' => $data['plan_id'],
                'reason' => $data['reason'] ?? null,
                'notify_tenant' => $data['notify_tenant'] ?? false,
                'ip_address' => $ip,
            ]
        );

        Log::info('Admin migrou plano de empresa', [
            'admin_id' => $admin->id,
            'tenant_id' => $data['tenant_id'],
            'plan_id' => $data['plan_id'],
            'reason' => $data['reason'] ?? null,
        ]);

        return $result;
    }

    public function getHistory(array $filters, int $limit = 50): mixed
    {
        return $this->planMigrationRepository->getFilteredHistory($filters, $limit);
    }

    public function bulkMigrate($admin, array $tenantIds, int $planId, ?string $reason, string $ip): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($tenantIds),
        ];

        foreach ($tenantIds as $tenantId) {
            try {
                $this->migrateTenant($admin, [
                    'tenant_id' => $tenantId,
                    'plan_id' => $planId,
                    'reason' => $reason,
                    'notify_tenant' => false,
                ], $ip);

                $results['success'][] = [
                    'tenant_id' => $tenantId,
                    'message' => 'Migrado com sucesso',
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Admin realizou migração em massa', [
            'admin_id' => $admin->id,
            'total' => count($tenantIds),
            'success' => count($results['success']),
            'failed' => count($results['failed']),
        ]);

        return $results;
    }
}
