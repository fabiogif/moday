<?php

namespace App\Services;

use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;

class AdminPlanLimitService
{
    public function __construct(
        private readonly PlanLimitService $planLimitService,
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly PlanRepositoryInterface $planRepository,
    ) {
    }

    /**
     * Retorna empresas que estão próximas aos limites (>= threshold%)
     *
     * @param int $threshold Percentual de uso (padrão: 80)
     * @param int|null $planId Filtrar por plano específico
     * @return array
     */
    public function getTenantsNearLimit(int $threshold = 80, ?int $planId = null): array
    {
        $tenants = $this->tenantRepository->getTenantsWithPlan(
            tenantIds: null,
            planId: $planId,
            status: 'active',
        );

        $result = [];

        foreach ($tenants as $tenant) {
            $plan = $tenant->plan;
            if (!$plan) {
                continue;
            }

            $usage = $this->planLimitService->getCurrentUsage($tenant->id);
            $limits = [
                'max_users' => $plan->max_users ?? 1,
                'max_products' => $plan->max_products ?? 50,
                'max_orders_per_month' => $plan->max_orders_per_month ?? 30,
            ];

            // Calcular percentual de uso para cada limite
            $usagePercentages = [];
            $isNearLimit = false;

            if ($limits['max_users'] !== null && $limits['max_users'] < 999999) {
                $percentage = ($usage['users'] / $limits['max_users']) * 100;
                $usagePercentages['users'] = round($percentage, 2);
                if ($percentage >= $threshold) {
                    $isNearLimit = true;
                }
            }

            if ($limits['max_products'] !== null && $limits['max_products'] < 999999) {
                $percentage = ($usage['products'] / $limits['max_products']) * 100;
                $usagePercentages['products'] = round($percentage, 2);
                if ($percentage >= $threshold) {
                    $isNearLimit = true;
                }
            }

            if ($limits['max_orders_per_month'] !== null) {
                $percentage = ($usage['orders_this_month'] / $limits['max_orders_per_month']) * 100;
                $usagePercentages['orders'] = round($percentage, 2);
                if ($percentage >= $threshold) {
                    $isNearLimit = true;
                }
            }

            if ($isNearLimit) {
                $result[] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'tenant_uuid' => $tenant->uuid,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'usage' => $usage,
                    'limits' => $limits,
                    'usage_percentages' => $usagePercentages,
                    'max_percentage' => !empty($usagePercentages) ? max($usagePercentages) : 0,
                ];
            }
        }

        // Ordenar por maior percentual de uso
        usort($result, function ($a, $b) {
            return $b['max_percentage'] <=> $a['max_percentage'];
        });

        return $result;
    }

    /**
     * Retorna empresas que atingiram algum limite
     *
     * @param int|null $planId Filtrar por plano específico
     * @return array
     */
    public function getTenantsAtLimit(?int $planId = null): array
    {
        $tenants = $this->tenantRepository->getTenantsWithPlan(
            tenantIds: null,
            planId: $planId,
            status: 'active',
        );

        $result = [];

        foreach ($tenants as $tenant) {
            $limitsCheck = $this->planLimitService->checkLimits($tenant->id);

            if ($limitsCheck['has_limit_reached']) {
                $result[] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'tenant_uuid' => $tenant->uuid,
                    'plan_id' => $tenant->plan_id,
                    'plan_name' => $limitsCheck['plan_name'],
                    'reached_limits' => $limitsCheck['reached_limits'],
                    'current_usage' => $limitsCheck['current_usage'],
                    'plan_limits' => $limitsCheck['plan_limits'],
                    'message' => $limitsCheck['message'],
                ];
            }
        }

        return $result;
    }

    /**
     * Relatório detalhado de uso de limites
     *
     * @param array|null $tenantIds Filtrar por IDs específicos
     * @param int|null $planId Filtrar por plano
     * @param string|null $status Filtrar por status do tenant
     * @return array
     */
    public function getTenantsUsageReport(?array $tenantIds = null, ?int $planId = null, ?string $status = null): array
    {
        $tenants = $this->tenantRepository->getTenantsWithPlan(
            tenantIds: $tenantIds,
            planId: $planId,
            status: $status,
        );

        $report = [
            'total_tenants' => $tenants->count(),
            'tenants' => [],
            'summary' => [
                'at_limit' => 0,
                'near_limit' => 0,
                'within_limits' => 0,
            ],
        ];

        foreach ($tenants as $tenant) {
            $limitsCheck = $this->planLimitService->checkLimits($tenant->id);
            $usage = $limitsCheck['current_usage'];
            $limits = $limitsCheck['plan_limits'];

            $tenantData = [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_uuid' => $tenant->uuid,
                'plan_id' => $tenant->plan_id,
                'plan_name' => $limitsCheck['plan_name'],
                'is_active' => $tenant->is_active,
                'usage' => $usage,
                'limits' => $limits,
                'has_limit_reached' => $limitsCheck['has_limit_reached'],
                'reached_limits' => $limitsCheck['reached_limits'],
            ];

            // Calcular percentuais
            $percentages = [];
            if ($limits['max_users'] !== null && $limits['max_users'] < 999999) {
                $percentages['users'] = round(($usage['users'] / $limits['max_users']) * 100, 2);
            }
            if ($limits['max_products'] !== null && $limits['max_products'] < 999999) {
                $percentages['products'] = round(($usage['products'] / $limits['max_products']) * 100, 2);
            }
            if ($limits['max_orders_per_month'] !== null) {
                $percentages['orders'] = round(($usage['orders_this_month'] / $limits['max_orders_per_month']) * 100, 2);
            }

            $tenantData['usage_percentages'] = $percentages;
            $tenantData['max_percentage'] = !empty($percentages) ? max($percentages) : 0;

            $report['tenants'][] = $tenantData;

            // Atualizar resumo
            if ($limitsCheck['has_limit_reached']) {
                $report['summary']['at_limit']++;
            } elseif ($tenantData['max_percentage'] >= 80) {
                $report['summary']['near_limit']++;
            } else {
                $report['summary']['within_limits']++;
            }
        }

        return $report;
    }

    /**
     * Distribuição de empresas por plano e estatísticas
     *
     * @return array
     */
    public function getPlanDistribution(): array
    {
        $plans = $this->planRepository->getActivePlans();
        $distribution = [];

        foreach ($plans as $plan) {
            $tenants = $this->tenantRepository->getTenantsWithPlan(
                tenantIds: null,
                planId: $plan->id,
                status: 'active',
            );

            $planData = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'total_tenants' => $tenants->count(),
                'tenants' => [],
                'average_usage' => [
                    'users' => 0,
                    'products' => 0,
                    'orders_this_month' => 0,
                ],
            ];

            $totalUsers = 0;
            $totalProducts = 0;
            $totalOrders = 0;
            $count = 0;

            foreach ($tenants as $tenant) {
                $usage = $this->planLimitService->getCurrentUsage($tenant->id);
                $planData['tenants'][] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'usage' => $usage,
                ];

                $totalUsers += $usage['users'];
                $totalProducts += $usage['products'];
                $totalOrders += $usage['orders_this_month'];
                $count++;
            }

            if ($count > 0) {
                $planData['average_usage'] = [
                    'users' => round($totalUsers / $count, 2),
                    'products' => round($totalProducts / $count, 2),
                    'orders_this_month' => round($totalOrders / $count, 2),
                ];
            }

            $distribution[] = $planData;
        }

        return $distribution;
    }

    /**
     * Detalhes de limites de uma empresa específica
     *
     * @param int $tenantId
     * @return array
     */
    public function getTenantLimits(int $tenantId): array
    {
        $tenant = $this->tenantRepository->getById($tenantId);

        if (!$tenant) {
            throw new \Exception('Tenant not found.');
        }

        $tenant->loadMissing('plan');

        $limitsCheck = $this->planLimitService->checkLimits($tenantId);
        $usage = $this->planLimitService->getCurrentUsage($tenantId);

        return [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'tenant_uuid' => $tenant->uuid,
            'plan_id' => $tenant->plan_id,
            'plan_name' => $limitsCheck['plan_name'],
            'is_active' => $tenant->is_active,
            'usage' => $usage,
            'limits' => $limitsCheck['plan_limits'],
            'has_limit_reached' => $limitsCheck['has_limit_reached'],
            'reached_limits' => $limitsCheck['reached_limits'],
            'message' => $limitsCheck['message'],
        ];
    }
}

