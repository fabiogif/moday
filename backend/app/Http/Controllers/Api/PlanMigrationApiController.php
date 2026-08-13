<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\Api\MigratePlanRequest;
use App\Services\PlanMigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class PlanMigrationApiController extends Controller
{
    public function __construct(
        private readonly PlanMigrationService $planMigrationService
    ) {
    }

    /**
     * Executa migração de plano
     * 
     * POST /api/plan/migrate
     */
    public function migrate(MigratePlanRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse('', 'Usuário não autenticado', 401);
            }

            $validated = $request->validated();
            $planId = (int) $validated['plan_id'];
            $notes = $validated['notes'] ?? null;

            $result = $this->planMigrationService->migratePlan(
                $user->tenant_id,
                $planId,
                $notes
            );

            return ApiResponseClass::sendResponse(
                $result['tenant'],
                $result['message'],
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao migrar plano');
        }
    }

    /**
     * Obtém histórico de migrações
     * 
     * GET /api/plan/migrations/history
     */
    public function history(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse('', 'Usuário não autenticado', 401);
            }

            $history = $this->planMigrationService->getMigrationHistory($user->tenant_id);

            return ApiResponseClass::sendResponse($history, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao obter histórico de migrações');
        }
    }
}
