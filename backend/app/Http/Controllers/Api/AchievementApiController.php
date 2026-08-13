<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\SalesGoal\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementApiController extends Controller
{
    public function __construct(
        private readonly AchievementService $achievementService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function index(): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $definitions = $this->achievementService->listDefinitions($tenantId);

            return ApiResponseClass::sendResponse($definitions, 'Conquistas recuperadas', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar conquistas');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $data = $request->validate([
                'key' => ['required', 'string', 'max:100'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'icon' => ['nullable', 'string', 'max:100'],
                'badge_color' => ['nullable', 'string', 'max:30'],
                'category' => ['required', 'string', 'in:sales,goals,ranking,streak'],
                'trigger_type' => ['required', 'string', 'in:order_count,revenue_threshold,goal_completion,ranking_position,streak_days'],
                'trigger_config' => ['required', 'array'],
                'points_reward' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
                'display_order' => ['nullable', 'integer', 'min:0'],
            ]);

            $definition = $this->achievementService->createDefinition($data, $tenantId);

            return ApiResponseClass::sendResponse($definition, 'Conquista criada com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar conquista');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $definition = $this->achievementService->findDefinition($tenantId, $id);

            if (!$definition) {
                return ApiResponseClass::sendResponse(null, 'Conquista não encontrada', 404);
            }

            return ApiResponseClass::sendResponse($definition, 'Conquista recuperada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar conquista');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $data = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'icon' => ['nullable', 'string', 'max:100'],
                'badge_color' => ['nullable', 'string', 'max:30'],
                'category' => ['sometimes', 'string', 'in:sales,goals,ranking,streak'],
                'trigger_type' => ['sometimes', 'string', 'in:order_count,revenue_threshold,goal_completion,ranking_position,streak_days'],
                'trigger_config' => ['sometimes', 'array'],
                'points_reward' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
                'display_order' => ['nullable', 'integer', 'min:0'],
            ]);

            $definition = $this->achievementService->updateDefinition($tenantId, $id, $data);

            if (!$definition) {
                return ApiResponseClass::sendResponse(null, 'Conquista não encontrada', 404);
            }

            return ApiResponseClass::sendResponse($definition, 'Conquista atualizada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar conquista');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $this->achievementService->deleteDefinition($tenantId, $id);

            return ApiResponseClass::sendResponse(null, 'Conquista excluída', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir conquista');
        }
    }

    public function userAchievements(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $achievements = $this->achievementService->getUserAchievements($tenantId, $user->id);

            return ApiResponseClass::sendResponse($achievements, 'Conquistas do usuário', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar conquistas');
        }
    }
}
