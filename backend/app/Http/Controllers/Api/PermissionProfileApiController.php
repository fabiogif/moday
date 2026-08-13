<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Http\Requests\PermissionProfileSyncRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\ProfileResource;
use App\Classes\ApiResponseClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AuthTenantService;
use App\Services\PermissionProfileService;

class PermissionProfileApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly PermissionProfileService $permissionProfileService
    ) {}

    /**
     * Get permissions for a specific profile.
     */
    public function getProfilePermissions($profileId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $permissions = $this->permissionProfileService->getProfilePermissions((int)$profileId, $tenantId);
            if ($permissions === null) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
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
    public function getAvailablePermissionsForProfile($profileId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $availablePermissions = $this->permissionProfileService->getAvailablePermissionsForProfile((int)$profileId, $tenantId);
            if ($availablePermissions === null) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            return ApiResponseClass::sendResponse(
                PermissionResource::collection($availablePermissions),
                'Permissões disponíveis recuperadas com sucesso'
            );
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar permissões disponíveis: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar permissões disponíveis');
        }
    }

    /**
     * Attach permission to profile.
     */
    public function attachPermissionToProfile(Request $request, $profileId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $validated = $request->validate([
                'permission_id' => 'required|integer'
            ]);
            $profile = $this->permissionProfileService->attachPermissionToProfile((int)$profileId, (int)$validated['permission_id'], $tenantId);
            if ($profile === null) {
                return ApiResponseClass::sendResponse(null, 'Perfil ou permissão não encontrada', 404);
            }
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
    public function detachPermissionFromProfile($profileId, $permissionId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $profile = $this->permissionProfileService->detachPermissionFromProfile((int)$profileId, (int)$permissionId, $tenantId);
            if ($profile === null) {
                return ApiResponseClass::sendResponse(null, 'Perfil ou permissão não encontrada', 404);
            }
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
     * Sync permissions for a profile.
     */
    public function syncPermissionsForProfile(PermissionProfileSyncRequest $request, $profileId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $validatedData = $request->validated();
            $profile = $this->permissionProfileService->syncPermissionsForProfile((int)$profileId, $validatedData['permission_ids'], $tenantId);
            if ($profile === null) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            return ApiResponseClass::sendResponse(
                new ProfileResource($profile->load(['permissions', 'tenant'])),
                'Permissões do perfil sincronizadas com sucesso'
            );
            
        } catch (\InvalidArgumentException $e) {
            if ($e->getMessage() === 'invalid_permissions') {
                return ApiResponseClass::sendResponse(null, 'Uma ou mais permissões não foram encontradas', 400);
            }
            Log::error('Erro de argumento ao sincronizar permissões do perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao sincronizar permissões do perfil');
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar permissões do perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao sincronizar permissões do perfil');
        }
    }

    /**
     * Get profiles for a specific permission.
     */
    public function getPermissionProfiles($permissionId): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $profiles = $this->permissionProfileService->getPermissionProfiles((int)$permissionId, $tenantId);
            if ($profiles === null) {
                return ApiResponseClass::sendResponse(null, 'Permissão não encontrada', 404);
            }
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
