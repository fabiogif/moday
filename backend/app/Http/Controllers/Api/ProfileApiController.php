<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Http\Requests\ProfileStoreRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use App\Classes\ApiResponseClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileApiController extends Controller
{
    /**
     * Display a listing of profiles.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 100);
            $filters = $request->only(['name', 'description', 'is_active']);
            
            $query = Profile::with(['permissions', 'tenant'])
                ->where('tenant_id', auth()->user()->tenant_id);
            
            // Apply filters
            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }
            
            if (isset($filters['description'])) {
                $query->where('description', 'like', '%' . $filters['description'] . '%');
            }
            
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }
            
            $profiles = $query->paginate($perPage);
            
            return ApiResponseClass::sendResponse([
                'profiles' => ProfileResource::collection($profiles->items()),
                'pagination' => [
                    'current_page' => $profiles->currentPage(),
                    'last_page' => $profiles->lastPage(),
                    'per_page' => $profiles->perPage(),
                    'total' => $profiles->total(),
                ]
            ], 'Perfis recuperados com sucesso');
            
        } catch (\Exception $e) {
            Log::error('Erro ao listar perfis: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar perfis');
        }
    }

    /**
     * Store a newly created profile.
     */
    public function store(ProfileStoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $validatedData = $request->validated();
            $validatedData['tenant_id'] = auth()->user()->tenant_id;
            
            $profile = Profile::create($validatedData);
            
            // Sync permissions if provided
            if (isset($validatedData['permissions'])) {
                $profile->permissions()->sync($validatedData['permissions']);
            }
            
            DB::commit();
            
            return ApiResponseClass::sendResponse(
                new ProfileResource($profile->load(['permissions', 'tenant'])),
                'Perfil criado com sucesso',
                201
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao criar perfil');
        }
    }

    /**
     * Display the specified profile.
     */
    public function show(Profile $profile): JsonResponse
    {
        try {
            // Verificar se o perfil pertence ao mesmo tenant
            if ($profile->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            
            return ApiResponseClass::sendResponse(
                new ProfileResource($profile->load(['permissions', 'tenant'])),
                'Perfil recuperado com sucesso'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar perfil');
        }
    }

    /**
     * Update the specified profile.
     */
    public function update(ProfileUpdateRequest $request, Profile $profile): JsonResponse
    {
        try {
            // Verificar se o perfil pertence ao mesmo tenant
            if ($profile->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            
            DB::beginTransaction();
            
            $validatedData = $request->validated();
            $profile->update($validatedData);
            
            // Sync permissions if provided
            if (isset($validatedData['permissions'])) {
                $profile->permissions()->sync($validatedData['permissions']);
            }
            
            DB::commit();
            
            return ApiResponseClass::sendResponse(
                new ProfileResource($profile->load(['permissions', 'tenant'])),
                'Perfil atualizado com sucesso'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao atualizar perfil');
        }
    }

    /**
     * Remove the specified profile.
     */
    public function destroy(Profile $profile): JsonResponse
    {
        try {
            // Verificar se o perfil pertence ao mesmo tenant
            if ($profile->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            
            // Verificar se o perfil está sendo usado por usuários
            $usersCount = $profile->users()->count();
            if ($usersCount > 0) {
                return ApiResponseClass::sendResponse(
                    null, 
                    "Não é possível excluir o perfil. Ele está sendo usado por {$usersCount} usuário(s).", 
                    400
                );
            }
            
            $profile->delete();
            
            return ApiResponseClass::sendResponse(null, 'Perfil excluído com sucesso');
            
        } catch (\Exception $e) {
            Log::error('Erro ao excluir perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao excluir perfil');
        }
    }
}
