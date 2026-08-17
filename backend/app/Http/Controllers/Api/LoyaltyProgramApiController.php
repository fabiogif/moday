<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLoyaltyProgramRequest;
use App\Http\Resources\LoyaltyProgramResource;
use App\Services\AuthTenantService;
use App\Services\LoyaltyProgramService;
use Illuminate\Http\JsonResponse;

class LoyaltyProgramApiController extends Controller
{
    public function __construct(
        private readonly LoyaltyProgramService $programService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function index(): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $program = $this->programService->getProgramByTenant($tenantId);

            return ApiResponseClass::sendResponse(
                $program ? new LoyaltyProgramResource($program) : null,
                'Programa recuperado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar programa');
        }
    }

    public function store(StoreLoyaltyProgramRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $program = $this->programService->createProgram($request->validated(), $tenantId);

            return ApiResponseClass::sendResponse(
                new LoyaltyProgramResource($program),
                'Programa criado com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar programa');
        }
    }

    public function update(StoreLoyaltyProgramRequest $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $program = $this->programService->getProgramByTenant($tenantId);

            if (!$program || $program->uuid !== $uuid) {
                return ApiResponseClass::sendResponse(null, 'Programa não encontrado', 404);
            }

            $updated = $this->programService->updateProgram($program->id, $request->validated());

            return ApiResponseClass::sendResponse(
                new LoyaltyProgramResource($updated),
                'Programa atualizado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar programa');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $program = $this->programService->getProgramByTenant($tenantId);

            if (!$program || $program->uuid !== $uuid) {
                return ApiResponseClass::sendResponse(null, 'Programa não encontrado', 404);
            }

            $this->programService->deleteProgram($program->id);

            return ApiResponseClass::sendResponse(null, 'Programa excluído com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir programa');
        }
    }
}

