<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLoyaltyRewardRequest;
use App\Http\Resources\LoyaltyRewardResource;
use App\Services\LoyaltyRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyRewardApiController extends Controller
{
    public function __construct(
        private readonly LoyaltyRewardService $rewardService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $available = $request->query('available') === 'true';
            $result = $this->rewardService->listForAuthenticatedTenant($available);

            if (!$result['program']) {
                return ApiResponseClass::sendResponse([], 'Nenhum programa de fidelidade configurado', 200);
            }

            return ApiResponseClass::sendResponse(
                LoyaltyRewardResource::collection($result['rewards']),
                'Recompensas recuperadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar recompensas');
        }
    }

    public function store(StoreLoyaltyRewardRequest $request): JsonResponse
    {
        try {
            $result = $this->rewardService->createForAuthenticatedTenant($request->validated());

            if (!$result['success']) {
                return ApiResponseClass::sendResponse(null, 'Configure um programa de fidelidade primeiro', 400);
            }

            return ApiResponseClass::sendResponse(
                new LoyaltyRewardResource($result['reward']),
                'Recompensa criada com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar recompensa');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $result = $this->rewardService->findForAuthenticatedTenant($uuid);

            if (!$result['success']) {
                return ApiResponseClass::sendResponse(null, 'Recompensa não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new LoyaltyRewardResource($result['reward']),
                'Recompensa recuperada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar recompensa');
        }
    }

    public function update(StoreLoyaltyRewardRequest $request, string $uuid): JsonResponse
    {
        try {
            $result = $this->rewardService->updateForAuthenticatedTenant($uuid, $request->validated());

            if (!$result['success']) {
                return ApiResponseClass::sendResponse(null, 'Recompensa não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new LoyaltyRewardResource($result['reward']),
                'Recompensa atualizada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar recompensa');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $result = $this->rewardService->deleteForAuthenticatedTenant($uuid);

            if (!$result['success']) {
                return ApiResponseClass::sendResponse(null, 'Recompensa não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(null, 'Recompensa excluída com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir recompensa');
        }
    }
}
