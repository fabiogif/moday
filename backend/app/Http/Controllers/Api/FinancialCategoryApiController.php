<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFinancialCategoryRequest;
use App\Http\Requests\Api\UpdateFinancialCategoryRequest;
use App\Http\Resources\FinancialCategoryResource;
use App\Services\AuthTenantService;
use App\Services\FinancialCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialCategoryApiController extends Controller
{
    public function __construct(
        private readonly FinancialCategoryService $categoryService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $type = $request->query('type'); // Filtrar por tipo se fornecido
            
            $categories = $type 
                ? $this->categoryService->getCategoriesByType($tenantId, $type)
                : $this->categoryService->getAllCategories($tenantId);

            return ApiResponseClass::sendResponse(
                FinancialCategoryResource::collection($categories),
                'Categorias recuperadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar categorias');
        }
    }

    public function store(StoreFinancialCategoryRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $category = $this->categoryService->createCategory($request->validated(), $tenantId);

            return ApiResponseClass::sendResponse(
                new FinancialCategoryResource($category),
                'Categoria criada com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar categoria');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $category = $this->categoryService->getCategoryByUuid($uuid);

            if (!$category || $category->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Categoria não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new FinancialCategoryResource($category),
                'Categoria recuperada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar categoria');
        }
    }

    public function update(UpdateFinancialCategoryRequest $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $category = $this->categoryService->getCategoryByUuid($uuid);

            if (!$category || $category->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Categoria não encontrada', 404);
            }

            $updated = $this->categoryService->updateCategory($category->id, $request->validated());

            return ApiResponseClass::sendResponse(
                new FinancialCategoryResource($updated),
                'Categoria atualizada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar categoria');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $category = $this->categoryService->getCategoryByUuid($uuid);

            if (!$category || $category->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Categoria não encontrada', 404);
            }

            $this->categoryService->deleteCategory($category->id);

            return ApiResponseClass::sendResponse(null, 'Categoria excluída com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir categoria');
        }
    }
}

