<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MigrateTenantPlanRequest;
use App\Services\AdminPlanMigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlanMigrationController extends Controller
{
    public function __construct(
        private readonly AdminPlanMigrationService $adminPlanMigrationService
    ) {}

    public function migrate(MigrateTenantPlanRequest $request): JsonResponse
    {
        try {
            $admin = auth('admin')->user();
            $result = $this->adminPlanMigrationService->migrateTenant(
                $admin,
                $request->validated(),
                $request->ip()
            );

            return ApiResponseClass::sendResponse($result, 'Plano migrado com sucesso.', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao migrar plano da empresa');
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['tenant_id', 'plan_id', 'date_from', 'date_to']);
            $limit = (int) $request->query('limit', 50);
            $migrations = $this->adminPlanMigrationService->getHistory($filters, $limit);

            return ApiResponseClass::sendResponse($migrations, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar histórico de migrações');
        }
    }

    public function bulkMigrate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tenant_ids' => 'required|array|min:1',
                'tenant_ids.*' => 'required|integer|exists:tenants,id',
                'plan_id' => [
                    'required',
                    'integer',
                    \Illuminate\Validation\Rule::exists('plans', 'id')->where(function ($query) {
                        $query->where('is_active', true);
                    }),
                ],
                'reason' => 'nullable|string|max:500',
            ]);

            $admin = auth('admin')->user();
            $results = $this->adminPlanMigrationService->bulkMigrate(
                $admin,
                $request->input('tenant_ids'),
                (int) $request->input('plan_id'),
                $request->input('reason'),
                $request->ip()
            );

            return ApiResponseClass::sendResponse($results, 'Migração em massa concluída.', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao realizar migração em massa');
        }
    }
}
