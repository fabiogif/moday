<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AdminPlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlanLimitController extends Controller
{
    public function __construct(
        private readonly AdminPlanLimitService $adminPlanLimitService
    ) {
    }

    /**
     * Retorna empresas próximas aos limites
     * 
     * GET /api/admin/plan-limits/tenants-near-limit
     * Query params: threshold (default: 80), plan_id (opcional)
     */
    public function tenantsNearLimit(Request $request): JsonResponse
    {
        try {
            $threshold = (int) $request->query('threshold', 80);
            $planId = $request->query('plan_id') ? (int) $request->query('plan_id') : null;

            $tenants = $this->adminPlanLimitService->getTenantsNearLimit($threshold, $planId);

            return ApiResponseClass::sendResponse($tenants, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar empresas próximas ao limite');
        }
    }

    /**
     * Retorna empresas que atingiram limites
     * 
     * GET /api/admin/plan-limits/tenants-at-limit
     * Query params: plan_id (opcional)
     */
    public function tenantsAtLimit(Request $request): JsonResponse
    {
        try {
            $planId = $request->query('plan_id') ? (int) $request->query('plan_id') : null;

            $tenants = $this->adminPlanLimitService->getTenantsAtLimit($planId);

            return ApiResponseClass::sendResponse($tenants, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar empresas no limite');
        }
    }

    /**
     * Relatório completo de uso de limites
     * 
     * GET /api/admin/plan-limits/usage-report
     * Query params: tenant_ids (array), plan_id, status
     */
    public function usageReport(Request $request): JsonResponse
    {
        try {
            $tenantIds = $request->query('tenant_ids');
            if ($tenantIds && is_string($tenantIds)) {
                $tenantIds = json_decode($tenantIds, true);
            }

            $planId = $request->query('plan_id') ? (int) $request->query('plan_id') : null;
            $status = $request->query('status'); // 'active' ou 'inactive'

            $report = $this->adminPlanLimitService->getTenantsUsageReport($tenantIds, $planId, $status);

            return ApiResponseClass::sendResponse($report, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar relatório de uso');
        }
    }

    /**
     * Distribuição de empresas por plano
     * 
     * GET /api/admin/plan-limits/plan-distribution
     */
    public function planDistribution(Request $request): JsonResponse
    {
        try {
            $distribution = $this->adminPlanLimitService->getPlanDistribution();

            return ApiResponseClass::sendResponse($distribution, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar distribuição de planos');
        }
    }

    /**
     * Detalhes de limites de uma empresa específica
     * 
     * GET /api/admin/plan-limits/tenant/{tenantId}/limits
     */
    public function tenantLimits(int $tenantId): JsonResponse
    {
        try {
            $limits = $this->adminPlanLimitService->getTenantLimits($tenantId);

            return ApiResponseClass::sendResponse($limits, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar limites da empresa');
        }
    }
}

