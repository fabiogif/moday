<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;
use App\Http\Requests\StoreUpdateTableRequest;
use App\Http\Resources\TableResource;
use App\Services\TableService;
use Illuminate\Http\{Request, Response, JsonResponse};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class TableApiController extends Controller
{
    public function __construct(protected TableService $tableService){}

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
            
            $table = $this->tableService->paginate(
                page: $request->get('page', 1),
                totalPerPage: $request->get('per_page', 15),
                filter: $request->filter ?? ''
            );
            
            return ApiResponseClass::sendResponsePaginate(TableResource::class, $table, 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar mesas');
        }
    }

    public function store(StoreUpdateTableRequest $request):JsonResponse
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
            
            $table = $this->tableService->store($data);
            return ApiResponseClass::sendResponse(new TableResource($table), 'Mesa cadastrada com sucesso', Response::HTTP_CREATED);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
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
        
        $table = $this->tableService->getTableByUuid($identify);
        if(!$table){
            return  ApiResponseClass::sendResponse('', 'Mesa não encontrada' ,Response::HTTP_NOT_FOUND);
        }
        return ApiResponseClass::sendResponse(new TableResource($table), '', 200);
    }

    public function update(StoreUpdateTableRequest $request, int $id): JsonResponse
    {
        try {
            $updated = $this->tableService->update($request->all(), $id);
            return ApiResponseClass::sendResponse(['updated' => (bool) $updated], 'Mesa atualizada com sucesso', 200);
        } catch (\Exception $ex) {
            Log::error('Erro ao atualizar mesa: '.$ex->getMessage());
            return ApiResponseClass::rollback($ex);
        }
    }

    public function delete(string $identify): JsonResponse
    {
        try {
            $deleted = $this->tableService->delete($identify);
            return ApiResponseClass::sendResponse(['deleted' => (bool) $deleted], 'Mesa removida com sucesso', 200);
        } catch (\Exception $ex) {
            Log::error('Erro ao remover mesa: '.$ex->getMessage());
            return ApiResponseClass::rollback($ex);
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
            
            $stats = $this->tableService->getStats($user->tenant_id);
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }
}
