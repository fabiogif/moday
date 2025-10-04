<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Classes\ApiResponseClass;
use App\Http\Resources\RoleResource;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RoleApiController extends Controller
{
    /**
     * Listar todos os roles do tenant do usuário autenticado
     */
    public function index(): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            $roles = Role::where('tenant_id', $user->tenant_id)
                ->with('permissions')
                ->orderBy('name')
                ->get();

            return ApiResponseClass::sendResponse(
                RoleResource::collection($roles),
                'Roles listados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar roles');
        }
    }

    /**
     * Criar um novo role
     */
    public function store(RoleStoreRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            $roleData = $request->validated();
            $roleData['tenant_id'] = $user->tenant_id;

            $role = Role::create($roleData);

            return ApiResponseClass::sendResponse(
                new RoleResource($role),
                'Role criado com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar role');
        }
    }

    /**
     * Mostrar um role específico
     */
    public function show(Role $role): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            // Verificar se o role pertence ao tenant do usuário
            if ($role->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            $role->load('permissions');

            return ApiResponseClass::sendResponse(
                new RoleResource($role),
                'Role encontrado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar role');
        }
    }

    /**
     * Atualizar um role
     */
    public function update(RoleUpdateRequest $request, Role $role): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            // Verificar se o role pertence ao tenant do usuário
            if ($role->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            $roleData = $request->validated();
            $role->update($roleData);

            return ApiResponseClass::sendResponse(
                new RoleResource($role->fresh()),
                'Role atualizado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar role');
        }
    }

    /**
     * Excluir um role
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            // Verificar se o role pertence ao tenant do usuário
            if ($role->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            // Verificar se o role está sendo usado por usuários
            $usersCount = $role->users()->count();
            if ($usersCount > 0) {
                return ApiResponseClass::sendResponse(
                    null,
                    "Não é possível excluir este role pois ele está sendo usado por {$usersCount} usuário(s)",
                    422
                );
            }

            $role->delete();

            return ApiResponseClass::sendResponse(
                null,
                'Role excluído com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir role');
        }
    }

    /**
     * Obter permissões de um role
     */
    public function getRolePermissions(Role $role): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            // Verificar se o role pertence ao tenant do usuário
            if ($role->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            $permissions = $role->permissions()->get();

            return ApiResponseClass::sendResponse(
                $permissions,
                'Permissões do role listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar permissões do role');
        }
    }

    /**
     * Associar permissão a um role
     */
    public function attachPermissionToRole(Request $request, Role $role): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            // Verificar se o role pertence ao tenant do usuário
            if ($role->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            $request->validate([
                'permission_id' => 'required|exists:permissions,id'
            ]);

            $permission = Permission::findOrFail($request->permission_id);
            
            // Verificar se a permissão pertence ao mesmo tenant
            if ($permission->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::forbidden('Acesso negado a esta permissão');
            }

            $role->permissions()->syncWithoutDetaching([$permission->id]);

            return ApiResponseClass::sendResponse(
                new RoleResource($role->fresh()),
                'Permissão associada ao role com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao associar permissão ao role');
        }
    }

    /**
     * Desassociar permissão de um role
     */
    public function detachPermissionFromRole(Role $role, Permission $permission): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            // Verificar se o role pertence ao tenant do usuário
            if ($role->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            // Verificar se a permissão pertence ao mesmo tenant
            if ($permission->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::forbidden('Acesso negado a esta permissão');
            }

            $role->permissions()->detach($permission->id);

            return ApiResponseClass::sendResponse(
                new RoleResource($role->fresh()),
                'Permissão desassociada do role com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao desassociar permissão do role');
        }
    }

    /**
     * Sincronizar permissões de um role
     */
    public function syncPermissionsForRole(Request $request, Role $role): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            // Verificar se o role pertence ao tenant do usuário
            if ($role->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::forbidden('Acesso negado a este role');
            }

            $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'exists:permissions,id'
            ]);

            // Verificar se todas as permissões pertencem ao mesmo tenant
            $permissions = Permission::whereIn('id', $request->permission_ids)
                ->where('tenant_id', $user->tenant_id)
                ->get();

            if ($permissions->count() !== count($request->permission_ids)) {
                return ApiResponseClass::sendResponse(
                    null,
                    'Uma ou mais permissões não pertencem ao seu tenant',
                    422
                );
            }

            $role->permissions()->sync($request->permission_ids);

            return ApiResponseClass::sendResponse(
                new RoleResource($role->fresh()),
                'Permissões sincronizadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao sincronizar permissões do role');
        }
    }
}
