<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\SalesGoal\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RankingApiController extends Controller
{
    public function __construct(
        private readonly RankingService $rankingService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function sellerRanking(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $period = $request->get('period', 'current_month');
            $sortBy = $request->get('sort_by', 'revenue');

            $ranking = $this->rankingService->getSellerRanking($tenantId, $period, $sortBy);

            return ApiResponseClass::sendResponse($ranking, 'Ranking de vendedores recuperado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar ranking');
        }
    }

    public function teamRanking(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $period = $request->get('period', 'current_month');
            $ranking = $this->rankingService->getTeamRanking($tenantId, $period);

            return ApiResponseClass::sendResponse($ranking, 'Ranking de equipes recuperado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar ranking de equipes');
        }
    }

    public function myPosition(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $period = $request->get('period', 'current_month');
            $position = $this->rankingService->getMyPosition($tenantId, $user->id, $period);

            return ApiResponseClass::sendResponse($position, 'Posição recuperada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar posição');
        }
    }
}
