<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;

class CategoryApiController extends Controller
{
    public function __construct(protected  CategoryService $categoryService){}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = auth()->user();
            
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
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar categorias');
        }
    }

    public function store(StoreCategoryRequest $request):JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $data = $request->all();
            $data['tenant_id'] = $user->tenant_id;
            
            // Debug log temporário
            \Log::info('CategoryApiController::store - Dados recebidos:', [
                'request_data' => $request->all(),
                'final_data' => $data,
                'user_tenant_id' => $user->tenant_id
            ]);
            
            $category = $this->categoryService->store($data);
            return ApiResponseClass::sendResponse(new CategoryResource($category), 'Categoria cadastrada com sucesso', 201);
        } catch (\Exception $ex) {
            \Log::error('CategoryApiController::store - Erro:', [
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);
            return ApiResponseClass::rollback($ex);
        }
    }

    public function update(StoreCategoryRequest $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $category = $this->categoryService->update($request->all(), $id, $user->tenant_id);
            if (!$category) {
                return ApiResponseClass::sendResponse('', 'Categoria não encontrada', 404);
            }
            return ApiResponseClass::sendResponse(new CategoryResource($category), 'Categoria atualizada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar categoria');
        }
    }
    public function show($identify):JsonResponse
    {
        $user = auth()->user();
        
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
    public function delete(string $identify): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $deleted = $this->categoryService->delete($identify, $user->tenant_id);
            if (!$deleted) {
                return ApiResponseClass::sendResponse('', 'Categoria não encontrada', 404);
            }
            return ApiResponseClass::sendResponse('', 'Categoria deletada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao deletar categoria');
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $stats = $this->categoryService->getStats($user->tenant_id);
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }
}
