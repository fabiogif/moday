<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSalesGoalRequest;
use App\Http\Requests\Api\UpdateSalesGoalRequest;
use App\Http\Resources\SalesGoalResource;
use App\Services\AuthTenantService;
use App\Services\SalesGoal\GoalProgressService;
use App\Services\SalesGoal\SalesGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesGoalApiController extends Controller
{
    public function __construct(
        private readonly SalesGoalService $salesGoalService,
        private readonly GoalProgressService $goalProgressService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $filters = $request->only(['goal_type', 'status', 'period_type', 'target_user_id']);
            $goals = $this->salesGoalService->listByTenant($tenantId, $filters);

            return ApiResponseClass::sendResponse(
                SalesGoalResource::collection($goals),
                'Metas recuperadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar metas');
        }
    }

    public function store(StoreSalesGoalRequest $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $goal = $this->salesGoalService->create($request->validated(), $tenantId, $user->id);

            return ApiResponseClass::sendResponse(
                new SalesGoalResource($goal),
                'Meta criada com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar meta');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $goal = $this->salesGoalService->find($tenantId, $id, ['progressLogs']);

            if (!$goal) {
                return ApiResponseClass::sendResponse(null, 'Meta não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new SalesGoalResource($goal),
                'Meta recuperada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar meta');
        }
    }

    public function update(UpdateSalesGoalRequest $request, int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $goal = $this->salesGoalService->update($tenantId, $id, $request->validated());

            if (!$goal) {
                return ApiResponseClass::sendResponse(null, 'Meta não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new SalesGoalResource($goal),
                'Meta atualizada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar meta');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $this->salesGoalService->delete($tenantId, $id);

            return ApiResponseClass::sendResponse(null, 'Meta excluída com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir meta');
        }
    }

    public function myGoals(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $goals = $this->salesGoalService->getMyGoals($tenantId, $user->id);

            return ApiResponseClass::sendResponse(
                SalesGoalResource::collection($goals),
                'Minhas metas recuperadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar minhas metas');
        }
    }

    public function recalculate(int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $goal = $this->salesGoalService->find($tenantId, $id);

            if (!$goal) {
                return ApiResponseClass::sendResponse(null, 'Meta não encontrada', 404);
            }

            $this->goalProgressService->recalculateGoalManually($goal);

            return ApiResponseClass::sendResponse(
                new SalesGoalResource($goal->fresh(['targetUser', 'targetProfile', 'targetProduct'])),
                'Meta recalculada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao recalcular meta');
        }
    }
}
