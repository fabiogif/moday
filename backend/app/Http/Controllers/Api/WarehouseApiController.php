<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\Stock\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly WarehouseService $warehouseService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $warehouses = $this->warehouseService->list($tenantId);

            return ApiResponseClass::sendResponse($warehouses, 'Armazéns recuperados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar armazéns');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'name'        => 'required|string|max:100',
                'description' => 'nullable|string',
                'type'        => 'sometimes|in:ambient,refrigerated,frozen',
                'address'     => 'nullable|string',
                'city'        => 'nullable|string',
                'state'       => 'nullable|string|max:2',
                'is_active'   => 'sometimes|boolean',
                'is_default'  => 'sometimes|boolean',
            ]);

            $warehouse = $this->warehouseService->create($tenantId, $validated);

            return ApiResponseClass::sendResponse($warehouse, 'Armazém criado com sucesso', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar armazém');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $warehouse = $this->warehouseService->find($tenantId, $id);
            if (!$warehouse) {
                return ApiResponseClass::sendResponse(null, 'Armazém não encontrado', 404);
            }

            return ApiResponseClass::sendResponse($warehouse, 'Armazém recuperado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar armazém');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'name'        => 'sometimes|string|max:100',
                'description' => 'nullable|string',
                'type'        => 'sometimes|in:ambient,refrigerated,frozen',
                'address'     => 'nullable|string',
                'city'        => 'nullable|string',
                'state'       => 'nullable|string|max:2',
                'is_active'   => 'sometimes|boolean',
                'is_default'  => 'sometimes|boolean',
            ]);

            $warehouse = $this->warehouseService->update($tenantId, $id, $validated);
            if (!$warehouse) {
                return ApiResponseClass::sendResponse(null, 'Armazém não encontrado', 404);
            }

            return ApiResponseClass::sendResponse($warehouse, 'Armazém atualizado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar armazém');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $warehouse = $this->warehouseService->deactivate($tenantId, $id);
            if (!$warehouse) {
                return ApiResponseClass::sendResponse(null, 'Armazém não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(null, 'Armazém desativado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao desativar armazém');
        }
    }
}
