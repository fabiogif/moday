<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStoreHourRequest;
use App\Http\Requests\Api\UpdateStoreHourRequest;
use App\Http\Resources\StoreHourResource;
use App\Services\AuthTenantService;
use App\Services\StoreHourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreHourApiController extends Controller
{
    public function __construct(
        private readonly StoreHourService $storeHourService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $filters = [
                'day_of_week' => $request->query('day_of_week'),
                'delivery_type' => $request->query('delivery_type'),
                'is_active' => $request->query('is_active'),
            ];

            $storeHours = $this->storeHourService->getAllStoreHours($tenantId, array_filter($filters));

            return ApiResponseClass::sendResponse(
                StoreHourResource::collection($storeHours),
                'Horários de funcionamento recuperados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar horários de funcionamento');
        }
    }

    public function store(StoreStoreHourRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $storeHour = $this->storeHourService->createStoreHour($request->validated(), $tenantId);

            return ApiResponseClass::sendResponse(
                new StoreHourResource($storeHour),
                'Horário de funcionamento criado com sucesso',
                201
            );
        } catch (\Illuminate\Database\QueryException $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar horario de funcionamento');
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
                'error' => $ex->getMessage(),
            ], 422);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $storeHour = $this->storeHourService->getStoreHourByUuid($uuid);

            if (!$storeHour || $storeHour->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Horário de funcionamento não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new StoreHourResource($storeHour),
                'Horário de funcionamento recuperado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar horário de funcionamento');
        }
    }

    public function update(UpdateStoreHourRequest $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $storeHour = $this->storeHourService->getStoreHourByUuid($uuid);

            if (!$storeHour || $storeHour->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Horário de funcionamento não encontrado', 404);
            }

            $updated = $this->storeHourService->updateStoreHour($storeHour->id, $request->validated());

            return ApiResponseClass::sendResponse(
                new StoreHourResource($updated),
                'Horário de funcionamento atualizado com sucesso',
                200
            );
        } catch (\Illuminate\Database\QueryException $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar horario de funcionamento');
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
                'error' => $ex->getMessage(),
            ], 422);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $storeHour = $this->storeHourService->getStoreHourByUuid($uuid);

            if (!$storeHour || $storeHour->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Horário de funcionamento não encontrado', 404);
            }

            $this->storeHourService->deleteStoreHour($storeHour->id);

            return ApiResponseClass::sendResponse(null, 'Horário de funcionamento excluído com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir horário de funcionamento');
        }
    }

    public function setAlwaysOpen(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $deliveryType = $request->input('delivery_type', 'both');

            $storeHour = $this->storeHourService->setAlwaysOpen($tenantId, $deliveryType);

            return ApiResponseClass::sendResponse(
                new StoreHourResource($storeHour),
                'Loja configurada como sempre aberta',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao configurar loja como sempre aberta');
        }
    }

    public function removeAlwaysOpen(): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $this->storeHourService->removeAlwaysOpen($tenantId);

            return ApiResponseClass::sendResponse(null, 'Configuração "sempre aberto" removida com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover configuração "sempre aberto"');
        }
    }

    public function checkIsOpen(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $deliveryType = $request->query('delivery_type', 'both');

            $isOpen = $this->storeHourService->isStoreOpen($tenantId, $deliveryType);

            return ApiResponseClass::sendResponse([
                'is_open' => $isOpen,
                'timestamp' => now()->toISOString(),
            ], 'Status verificado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao verificar status da loja');
        }
    }

    public function stats(): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $stats = $this->storeHourService->getStats($tenantId);

            return ApiResponseClass::sendResponse($stats, 'Estatísticas recuperadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar estatísticas');
        }
    }
}

