<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;
use App\Http\Requests\StoreServiceTypeRequest;
use App\Http\Requests\UpdateServiceTypeRequest;
use App\Http\Resources\ServiceTypeResource;
use App\Services\ServiceTypeService;
use Illuminate\Http\{Request, Response, JsonResponse};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServiceTypeApiController extends Controller
{
    public function __construct(protected ServiceTypeService $serviceTypeService) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            $serviceTypes = $this->serviceTypeService->paginate(
                page: $request->get('page', 1),
                totalPerPage: $request->get('per_page', 15),
                filter: $request->filter ?? ''
            );
            
            return ApiResponseClass::sendResponsePaginate(ServiceTypeResource::class, $serviceTypes, 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar tipos de atendimento');
        }
    }

    public function store(StoreServiceTypeRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $serviceType = $this->serviceTypeService->store($data);
            return ApiResponseClass::sendResponse(
                new ServiceTypeResource($serviceType),
                'Tipo de atendimento cadastrado com sucesso',
                Response::HTTP_CREATED
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function show($identify): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            $serviceType = $this->serviceTypeService->getServiceTypeByIdentify($identify);
            if (!$serviceType) {
                return ApiResponseClass::sendResponse('', 'Tipo de atendimento não encontrado', Response::HTTP_NOT_FOUND);
            }
            return ApiResponseClass::sendResponse(new ServiceTypeResource($serviceType), '', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar tipo de atendimento');
        }
    }

    public function update(UpdateServiceTypeRequest $request, int $id): JsonResponse
    {
        try {
            $updated = $this->serviceTypeService->update($request->validated(), $id);
            return ApiResponseClass::sendResponse(
                ['updated' => (bool) $updated],
                'Tipo de atendimento atualizado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            Log::error('Erro ao atualizar tipo de atendimento: ' . $ex->getMessage());
            return ApiResponseClass::rollback($ex);
        }
    }

    public function delete(string $identify): JsonResponse
    {
        try {
            Log::info('Tentando deletar tipo de atendimento', ['identify' => $identify]);
            $deleted = $this->serviceTypeService->delete($identify);
            Log::info('Resultado da exclusão', ['deleted' => $deleted, 'identify' => $identify]);
            
            if (!$deleted) {
                Log::warning('Tipo de atendimento não encontrado para exclusão', ['identify' => $identify]);
            }
            
            return ApiResponseClass::sendResponse(
                ['deleted' => (bool) $deleted],
                'Tipo de atendimento removido com sucesso',
                200
            );
        } catch (\Exception $ex) {
            Log::error('Erro ao remover tipo de atendimento: ' . $ex->getMessage(), [
                'identify' => $identify,
                'trace' => $ex->getTraceAsString()
            ]);
            return ApiResponseClass::rollback($ex);
        }
    }

    public function active(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            $serviceTypes = $this->serviceTypeService->getActiveServiceTypes();
            return ApiResponseClass::sendResponse(
                ServiceTypeResource::collection($serviceTypes),
                'Tipos de atendimento ativos carregados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar tipos de atendimento ativos');
        }
    }

    public function menu(): JsonResponse
    {
        try {
            $serviceTypes = $this->serviceTypeService->getMenuServiceTypes();
            return ApiResponseClass::sendResponse(
                ServiceTypeResource::collection($serviceTypes),
                'Tipos de atendimento do menu carregados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar tipos de atendimento do menu');
        }
    }
}






