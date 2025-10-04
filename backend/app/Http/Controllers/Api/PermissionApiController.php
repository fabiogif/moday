<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Http\Requests\PermissionStoreRequest;
use App\Http\Requests\PermissionUpdateRequest;
use App\Http\Resources\PermissionResource;
use App\Services\PermissionService;
use App\Classes\ApiResponseClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionApiController extends Controller
{
    public function __construct(protected PermissionService $permissionService)
    {
    }

    /**
     * Display a listing of permissions.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $filters = $request->only(['name', 'description', 'resource']);
            
            $permissions = $this->permissionService->getPermissionsByTenant($tenantId, $filters, $perPage);
            
            return ApiResponseClass::sendResponse([
                'permissions' => PermissionResource::collection($permissions->items()),
                'pagination' => [
                    'current_page' => $permissions->currentPage(),
                    'last_page' => $permissions->lastPage(),
                    'per_page' => $permissions->perPage(),
                    'total' => $permissions->total(),
                ]
            ], 'Permissões listadas com sucesso', 200);

        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar permissões');
        }
    }

    /**
     * Store a newly created permission in storage.
     */
    public function store(PermissionStoreRequest $request): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $permission = $this->permissionService->createPermission([
                'name' => $request->name,
                'description' => $request->description,
                'resource' => $request->resource,
                'action' => $request->action,
                'module' => $request->module,
                'slug' => $request->slug,
                'is_active' => $request->is_active ?? true,
            ], $tenantId);

            return ApiResponseClass::sendResponse(
                new PermissionResource($permission),
                'Permissão criada com sucesso',
                201
            );

        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar permissão');
        }
    }

    /**
     * Display the specified permission.
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $permission = $this->permissionService->getPermissionByUuid($uuid, $tenantId);

            if (!$permission) {
                return ApiResponseClass::sendResponse('', 'Permissão não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new PermissionResource($permission),
                'Permissão encontrada',
                200
            );

        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar permissão');
        }
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(PermissionUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $permission = $this->permissionService->updatePermission($uuid, [
                'name' => $request->name,
                'description' => $request->description,
                'resource' => $request->resource,
                'action' => $request->action,
                'module' => $request->module,
                'slug' => $request->slug,
                'is_active' => $request->is_active ?? true,
            ], $tenantId);

            if (!$permission) {
                return ApiResponseClass::sendResponse('', 'Permissão não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new PermissionResource($permission),
                'Permissão atualizada com sucesso',
                200
            );

        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar permissão');
        }
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $deleted = $this->permissionService->deletePermission($uuid, $tenantId);

            if (!$deleted) {
                return ApiResponseClass::sendResponse('', 'Permissão não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                '',
                'Permissão excluída com sucesso',
                200
            );

        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir permissão');
        }
    }
}