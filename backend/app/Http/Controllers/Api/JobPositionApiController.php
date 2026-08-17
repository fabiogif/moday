<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJobPositionRequest;
use App\Http\Requests\Api\UpdateJobPositionRequest;
use App\Http\Resources\JobPositionResource;
use App\Services\AuthTenantService;
use App\Services\JobPositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class JobPositionApiController extends Controller
{
    public function __construct(
        private readonly JobPositionService $jobPositionService,
        private readonly AuthTenantService $authTenantService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $activeOnly = $request->boolean('active_only');
            $positions = $this->jobPositionService->getAllPositions($tenantId, $activeOnly);

            return ApiResponseClass::sendResponse(
                JobPositionResource::collection($positions),
                'Cargos recuperados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar cargos');
        }
    }

    public function store(StoreJobPositionRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $position = $this->jobPositionService->createPosition($request->validated(), $tenantId);

            return ApiResponseClass::sendResponse(
                new JobPositionResource($position),
                'Cargo criado com sucesso',
                201
            );
        } catch (InvalidArgumentException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar cargo');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $position = $this->jobPositionService->getPositionByUuid($uuid);
            if (!$position || $position->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Cargo não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new JobPositionResource($position),
                'Cargo recuperado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar cargo');
        }
    }

    public function update(UpdateJobPositionRequest $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $position = $this->jobPositionService->getPositionByUuid($uuid);
            if (!$position || $position->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Cargo não encontrado', 404);
            }

            $updated = $this->jobPositionService->updatePosition($position->id, $request->validated());

            return ApiResponseClass::sendResponse(
                new JobPositionResource($updated),
                'Cargo atualizado com sucesso',
                200
            );
        } catch (InvalidArgumentException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar cargo');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $position = $this->jobPositionService->getPositionByUuid($uuid);
            if (!$position || $position->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Cargo não encontrado', 404);
            }

            $this->jobPositionService->deletePosition($position->id);

            return ApiResponseClass::sendResponse(null, 'Cargo excluído com sucesso', 200);
        } catch (InvalidArgumentException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir cargo');
        }
    }
}
