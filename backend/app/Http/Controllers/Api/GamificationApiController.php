<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\SalesGoal\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamificationApiController extends Controller
{
    public function __construct(
        private readonly GamificationService $gamificationService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function profile(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $profile = $this->gamificationService->getProfile($tenantId, $user->id);

            return ApiResponseClass::sendResponse($profile, 'Perfil de gamificação', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar perfil');
        }
    }

    public function leaderboard(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $limit = (int) $request->get('limit', 20);
            $ranking = $this->gamificationService->getPointsLeaderboard($tenantId, min($limit, 100));

            return ApiResponseClass::sendResponse($ranking, 'Leaderboard de pontos', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar leaderboard');
        }
    }

    public function pointHistory(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $limit = (int) $request->get('limit', 50);
            $history = $this->gamificationService->getPointHistory($tenantId, $user->id, min($limit, 200));

            return ApiResponseClass::sendResponse($history, 'Histórico de pontos', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar histórico');
        }
    }
}
