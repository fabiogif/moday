<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class PlanLimitApiController extends Controller
{
    public function __construct(
        private readonly PlanLimitService $planLimitService
    ) {
    }

    /**
     * Verifica se algum limite foi atingido
     * 
     * GET /api/plan/limits/check
     */
    public function checkLimits(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse('', 'Usuário não autenticado', 401);
            }

            $result = $this->planLimitService->checkLimits($user->tenant_id);

            return ApiResponseClass::sendResponse($result, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao verificar limites do plano');
        }
    }

    /**
     * Retorna o uso atual do tenant
     * 
     * GET /api/plan/current-usage
     */
    public function getCurrentUsage(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::sendResponse('', 'Usuário não autenticado', 401);
            }

            $usage = $this->planLimitService->getCurrentUsage($user->tenant_id);

            return ApiResponseClass::sendResponse($usage, '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao obter uso atual');
        }
    }
}
