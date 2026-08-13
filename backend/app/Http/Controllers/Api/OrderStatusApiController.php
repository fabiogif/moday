<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\{StoreOrderStatusRequest, UpdateOrderStatusRequest, ReorderOrderStatusRequest};
use App\Services\OrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderStatusApiController extends Controller
{
    public function __construct(
        protected OrderStatusService $service
    ) {}

    /**
     * Lista todos os status do tenant
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $activeOnly = $request->boolean('active_only', false);
            
            $statuses = $this->service->getAllStatuses($tenantId, $activeOnly);
            
            return ApiResponseClass::sendResponse(
                $statuses,
                'Status listados com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * Exibe um status específico
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $status = $this->service->getStatusByUuid($uuid);
            
            if (!$status) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Status não encontrado',
                    404
                );
            }
            
            return ApiResponseClass::sendResponse(
                $status,
                'Status encontrado',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * Cria um novo status
     */
    public function store(StoreOrderStatusRequest $request): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $status = $this->service->createStatus($tenantId, $request->validated());
            
            return ApiResponseClass::sendResponse(
                $status,
                'Status criado com sucesso',
                201
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * Atualiza um status existente
     */
    public function update(UpdateOrderStatusRequest $request, string $uuid): JsonResponse
    {
        try {
            $status = $this->service->updateStatus($uuid, $request->validated());
            
            return ApiResponseClass::sendResponse(
                $status,
                'Status atualizado com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * Deleta um status
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $deleted = $this->service->deleteStatus($uuid);
            
            if (!$deleted) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Status não encontrado',
                    404
                );
            }
            
            return ApiResponseClass::sendResponse(
                '',
                'Status deletado com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * Reordena os status
     */
    public function reorder(ReorderOrderStatusRequest $request): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $this->service->reorderStatuses($tenantId, $request->validated('order'));
            
            return ApiResponseClass::sendResponse(
                '',
                'Ordem atualizada com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }
}

