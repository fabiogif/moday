<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RedeemRewardRequest;
use App\Http\Resources\{LoyaltyTransactionResource, LoyaltyRedemptionResource};
use App\Services\AuthTenantService;
use App\Services\LoyaltyProgramService;
use App\Services\LoyaltyPointsService;
use App\Services\LoyaltyRedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyClientApiController extends Controller
{
    public function __construct(
        private readonly LoyaltyProgramService $programService,
        private readonly LoyaltyPointsService $pointsService,
        private readonly LoyaltyRedemptionService $redemptionService,
        private readonly AuthTenantService $authTenantService
    ) {}

    /**
     * Obtém saldo de pontos de um cliente
     */
    public function balance(int $clientId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $program = $this->programService->getActiveProgramByTenant($tenantId);

            if (!$program) {
                return ApiResponseClass::sendResponse([
                    'balance' => 0,
                    'available_points' => 0,
                    'has_program' => false,
                ], 'Nenhum programa ativo', 200);
            }

            $balance = $this->pointsService->getClientBalance($clientId, $program->id);
            $available = $this->pointsService->getAvailablePoints($clientId, $program->id);

            return ApiResponseClass::sendResponse([
                'balance' => $balance,
                'available_points' => $available,
                'has_program' => true,
                'program_name' => $program->name,
            ], 'Saldo recuperado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar saldo');
        }
    }

    /**
     * Obtém histórico de transações de um cliente
     */
    public function transactions(int $clientId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $program = $this->programService->getActiveProgramByTenant($tenantId);

            if (!$program) {
                return ApiResponseClass::sendResponse([], 'Nenhum programa ativo', 200);
            }

            $transactions = $this->pointsService->getTransactionHistory($clientId, $program->id);

            return ApiResponseClass::sendResponse(
                LoyaltyTransactionResource::collection($transactions),
                'Histórico recuperado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar histórico');
        }
    }

    /**
     * Resgata uma recompensa
     */
    public function redeem(RedeemRewardRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $result = $this->redemptionService->redeemReward(
                $request->validated()['client_id'],
                $request->validated()['reward_id']
            );

            return ApiResponseClass::sendResponse($result, 'Recompensa resgatada com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    /**
     * Obtém resgates de um cliente
     */
    public function redemptions(int $clientId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            
            $redemptions = $this->redemptionService->getClientRedemptions($clientId);

            return ApiResponseClass::sendResponse(
                LoyaltyRedemptionResource::collection($redemptions),
                'Resgates recuperados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar resgates');
        }
    }

    /**
     * Ajusta pontos manualmente (admin)
     */
    public function adjustPoints(Request $request, int $clientId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $program = $this->programService->getActiveProgramByTenant($tenantId);

            if (!$program) {
                return ApiResponseClass::sendResponse(null, 'Nenhum programa ativo', 400);
            }

            $request->validate([
                'points' => ['required', 'integer'],
                'description' => ['required', 'string', 'max:255'],
            ]);

            $this->pointsService->adjustPoints(
                $clientId,
                $program->id,
                $request->input('points'),
                $request->input('description')
            );

            return ApiResponseClass::sendResponse(null, 'Pontos ajustados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao ajustar pontos');
        }
    }
}

