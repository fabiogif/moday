<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Http\Requests\PermissionProfileSyncRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\ProfileResource;
use App\Models\Permission;
use App\Models\Profile;
use App\Classes\ApiResponseClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionProfileApiController extends Controller
{
    /**
     * Get permissions for a specific profile.
     */
    public function getProfilePermissions(Profile $profile): JsonResponse
    {
        try {
            // Verificar se o perfil pertence ao mesmo tenant
            if ($profile->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            
            $permissions = $profile->permissions()->get();
            
            return ApiResponseClass::sendResponse(
                PermissionResource::collection($permissions),
                'Permissões do perfil recuperadas com sucesso'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar permissões do perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar permissões do perfil');
        }
    }

    /**
     * Get available permissions for a profile (not already assigned).
     */
    public function getAvailablePermissionsForProfile(Profile $profile): JsonResponse
    {
        try {
            // Verificar se o perfil pertence ao mesmo tenant
            if ($profile->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            
            $assignedPermissionIds = $profile->permissions()->pluck('permissions.id');
            
            $availablePermissions = Permission::where('tenant_id', auth()->user()->tenant_id)
                ->whereNotIn('id', $assignedPermissionIds)
                ->get();
            
            return ApiResponseClass::sendResponse(
                PermissionResource::collection($availablePermissions),
                'Permissões disponíveis para o perfil recuperadas com sucesso'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar permissões disponíveis: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar permissões disponíveis');
        }
    }

    /**
     * Attach permission to profile.
     */
    public function attachPermissionToProfile(Request $request, Profile $profile): JsonResponse
    {
        try {
            // Verificar se o perfil pertence ao mesmo tenant
            if ($profile->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            
            $request->validate([
                'permission_id' => 'required|exists:permissions,id'
            ]);
            
            $permission = Permission::find($request->permission_id);
            
            // Verificar se a permissão pertence ao mesmo tenant
            if (!$permission || $permission->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Permissão não encontrada', 404);
            }
            
            // Verificar se a permissão já está atribuída
            if ($profile->permissions()->where('permission_id', $permission->id)->exists()) {
                return ApiResponseClass::sendResponse(null, 'Permissão já está atribuída ao perfil', 400);
            }
            
            $profile->permissions()->attach($permission->id);
            
            return ApiResponseClass::sendResponse(
                new ProfileResource($profile->load(['permissions', 'tenant'])),
                'Permissão atribuída ao perfil com sucesso'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro ao atribuir permissão ao perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao atribuir permissão ao perfil');
        }
    }

    /**
     * Detach permission from profile.
     */
    public function detachPermissionFromProfile(Profile $profile, Permission $permission): JsonResponse
    {
        try {
            // Verificar se o perfil pertence ao mesmo tenant
            if ($profile->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            
            // Verificar se a permissão pertence ao mesmo tenant
            if ($permission->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Permissão não encontrada', 404);
            }
            
            // Verificar se a permissão está atribuída ao perfil
            if (!$profile->permissions()->where('permission_id', $permission->id)->exists()) {
                return ApiResponseClass::sendResponse(null, 'Permissão não está atribuída ao perfil', 400);
            }
            
            $profile->permissions()->detach($permission->id);
            
            return ApiResponseClass::sendResponse(
                new ProfileResource($profile->load(['permissions', 'tenant'])),
                'Permissão removida do perfil com sucesso'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro ao remover permissão do perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao remover permissão do perfil');
        }
    }

    /**
     * Sync permissions for profile (replace all permissions).
     */
    public function syncPermissionsForProfile(PermissionProfileSyncRequest $request, Profile $profile): JsonResponse
    {
        try {
            // Verificar se o perfil pertence ao mesmo tenant
            if ($profile->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            
            DB::beginTransaction();
            
            $validatedData = $request->validated();
            
            // Verificar se todas as permissões pertencem ao mesmo tenant
            $permissionIds = $validatedData['permission_ids'];
            $permissions = Permission::whereIn('id', $permissionIds)
                ->where('tenant_id', auth()->user()->tenant_id)
                ->get();
            
            if ($permissions->count() !== count($permissionIds)) {
                return ApiResponseClass::sendResponse(null, 'Uma ou mais permissões não foram encontradas', 400);
            }
            
            $profile->permissions()->sync($permissionIds);
            
            DB::commit();
            
            return ApiResponseClass::sendResponse(
                new ProfileResource($profile->load(['permissions', 'tenant'])),
                'Permissões do perfil sincronizadas com sucesso'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao sincronizar permissões do perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao sincronizar permissões do perfil');
        }
    }

    /**
     * Get profiles for a specific permission.
     */
    public function getPermissionProfiles(Permission $permission): JsonResponse
    {
        try {
            // Verificar se a permissão pertence ao mesmo tenant
            if ($permission->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Permissão não encontrada', 404);
            }
            
            $profiles = $permission->profiles()->get();
            
            return ApiResponseClass::sendResponse(
                ProfileResource::collection($profiles),
                'Perfis da permissão recuperados com sucesso'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar perfis da permissão: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar perfis da permissão');
        }
    }
}
