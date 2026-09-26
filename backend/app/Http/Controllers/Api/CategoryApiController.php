<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\ReorderCategoryRequest;

class CategoryApiController extends Controller
{
    public function __construct(protected  CategoryService $categoryService){}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $categories = $this->categoryService->paginate(
                page: $request->get('page', 1),
                totalPerPage: $request->get('per_page', 10),
                filter: $request->filter ?? '',
                tenantId: $user->tenant_id
            );
            return ApiResponseClass::sendResponsePaginate(CategoryResource::class, $categories, 200);
        } catch (\Throwable $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar categorias');
        }
    }

    public function store(StoreCategoryRequest $request):JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $data = $request->validated();
            $data['tenant_id'] = $user->tenant_id;

            $category = $this->categoryService->store($data);
            return ApiResponseClass::sendResponse(new CategoryResource($category), 'Categoria cadastrada com sucesso', 201);
        } catch (\Throwable $ex) {
            Log::error('CategoryApiController::store - Erro:', [
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao cadastrar categoria');
        }
    }

    public function update(StoreCategoryRequest $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $category = $this->categoryService->update($request->validated(), $id, $user->tenant_id);
            if (!$category) {
                return ApiResponseClass::sendResponse('', 'Categoria não encontrada', 404);
            }
            return ApiResponseClass::sendResponse(new CategoryResource($category), 'Categoria atualizada com sucesso', 200);
        } catch (\Throwable $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar categoria');
        }
    }
    public function show($identify):JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return ApiResponseClass::unauthorized('Usuário não autenticado');
        }
        
        if (!$user->tenant_id) {
            return ApiResponseClass::forbidden('Usuário não possui tenant associado');
        }
        
        $category = $this->categoryService->getByUuid($identify, $user->tenant_id);
        if(!$category){
            return  ApiResponseClass::sendResponse('', 'Categoria não encontrada' ,404);
        }

        return ApiResponseClass::sendResponse(new CategoryResource($category), '', 200);
    }
    public function inactivate(string $identify): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            // Regra: bloquear inativação se houver produtos ativos vinculados
            $guard = app(\App\Services\DeletionGuardService::class);
            $check = $guard->checkCategoryHasActiveProducts($identify, $user->tenant_id);
            if ($check['blocked']) {
                return ApiResponseClass::validationError([
                    'linked_products' => $check['products']
                ], 'Não é possível inativar: existem produtos ativos vinculados à categoria');
            }

            $inactivated = $this->categoryService->inactivate($identify, $user->tenant_id);
            if (!$inactivated) {
                return ApiResponseClass::sendResponse('', 'Categoria não encontrada', 404);
            }
            return ApiResponseClass::sendResponse(
                new CategoryResource($inactivated),
                'Categoria inativada com sucesso',
                200
            );
        } catch (\Throwable $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao inativar categoria');
        }
    }

    public function delete(string $identify): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            // Regra: bloquear exclusão se houver produtos ativos vinculados
            $guard = app(\App\Services\DeletionGuardService::class);
            $check = $guard->checkCategoryHasActiveProducts($identify, $user->tenant_id);
            if ($check['blocked']) {
                return ApiResponseClass::validationError([
                    'linked_products' => $check['products']
                ], 'Não é possível excluir: existem produtos ativos vinculados à categoria');
            }

            $deleted = $this->categoryService->delete($identify, $user->tenant_id);
            if (!$deleted) {
                return ApiResponseClass::sendResponse('', 'Categoria não encontrada', 404);
            }
            return ApiResponseClass::sendResponse(
                ['deleted' => true],
                'Categoria excluída com sucesso',
                200
            );
        } catch (\Throwable $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir categoria');
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $stats = $this->categoryService->getStats($user->tenant_id);
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Throwable $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }

    public function active(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            $categories = $this->categoryService->getActiveByTenant($user->tenant_id);

            return ApiResponseClass::sendResponse(
                CategoryResource::collection($categories),
                'Categorias ativas listadas com sucesso',
                200
            );
        } catch (\Throwable $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar categorias ativas');
        }
    }

    public function reorder(ReorderCategoryRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            $this->categoryService->reorderCategories($user->tenant_id, $request->validated('order'));

            return ApiResponseClass::sendResponse(
                '',
                'Ordem das categorias atualizada com sucesso',
                200
            );
        } catch (\Throwable $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao reordenar categorias');
        }
    }
}
