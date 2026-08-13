<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Classes\ApiResponseClass;
use App\Http\Requests\AttachPermissionToRoleRequest;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Http\Requests\SyncRolePermissionsRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;

class RoleApiController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $roles = $this->roleService->getRolesForCurrentTenant();

            if ($roles === null) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            return ApiResponseClass::sendResponse(
                RoleResource::collection($roles),
                'Roles listados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar roles');
        }
    }

    public function store(RoleStoreRequest $request): JsonResponse
    {
        try {
            $role = $this->roleService->createRoleForCurrentTenant($request->validated());

            if (!$role) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            return ApiResponseClass::sendResponse(
                new RoleResource($role),
                'Role criado com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar role');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $role = $this->roleService->findRoleForCurrentTenant($id);

            if (!$role) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            return ApiResponseClass::sendResponse(
                new RoleResource($role),
                'Role encontrado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar role');
        }
    }

    public function update(RoleUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $updatedRole = $this->roleService->updateRoleForCurrentTenant($id, $request->validated());

            if (!$updatedRole) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            return ApiResponseClass::sendResponse(
                new RoleResource($updatedRole),
                'Role atualizado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar role');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->roleService->deleteRoleForCurrentTenant($id);

            if (($result['reason'] ?? '') === 'not_found') {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            if (($result['reason'] ?? '') === 'in_use') {
                return ApiResponseClass::sendResponse(
                    null,
                    "Não é possível excluir este role pois ele está sendo usado por {$result['users_count']} usuário(s)",
                    422
                );
            }

            if (!($result['success'] ?? false)) {
                return ApiResponseClass::sendResponse(null, 'Não foi possível excluir este role', 422);
            }

            return ApiResponseClass::sendResponse(null, 'Role excluído com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir role');
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = $this->roleService->getRoleStatsForCurrentTenant();

            if ($stats === null) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }

    public function getRolePermissions(int $id): JsonResponse
    {
        try {
            $permissions = $this->roleService->getRolePermissionsForCurrentTenant($id);

            if ($permissions === null) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            return ApiResponseClass::sendResponse(
                $permissions,
                'Permissões do role listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar permissões do role');
        }
    }

    public function attachPermissionToRole(AttachPermissionToRoleRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $updatedRole = $this->roleService->attachPermissionToRoleForCurrentTenant(
                $id,
                (int) $data['permission_id']
            );

            if (!$updatedRole) {
                return ApiResponseClass::forbidden('Acesso negado a este role ou permissão');
            }

            return ApiResponseClass::sendResponse(
                new RoleResource($updatedRole),
                'Permissão associada ao role com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao associar permissão ao role');
        }
    }

    public function detachPermissionFromRole(int $id, int $permissionId): JsonResponse
    {
        try {
            $updatedRole = $this->roleService->detachPermissionFromRoleForCurrentTenant($id, $permissionId);

            if (!$updatedRole) {
                return ApiResponseClass::forbidden('Acesso negado a este role ou permissão');
            }

            return ApiResponseClass::sendResponse(
                new RoleResource($updatedRole),
                'Permissão desassociada do role com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao desassociar permissão do role');
        }
    }

    public function syncPermissionsForRole(SyncRolePermissionsRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $updatedRole = $this->roleService->syncPermissionsForRoleForCurrentTenant($id, $data['permission_ids']);

            if (!$updatedRole) {
                return ApiResponseClass::sendResponse(
                    null,
                    'Uma ou mais permissões não pertencem ao seu tenant',
                    422
                );
            }

            return ApiResponseClass::sendResponse(
                new RoleResource($updatedRole),
                'Permissões sincronizadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao sincronizar permissões do role');
        }
    }
}
