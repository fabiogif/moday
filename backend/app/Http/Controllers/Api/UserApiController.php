<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UserAssignProfileRequest;
use App\Http\Requests\UserChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Profile;
use App\Services\UserService;
use App\Classes\ApiResponseClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserApiController extends BaseController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Verificação temporária - permitir acesso se o usuário está autenticado
            if (!auth()->check()) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }
            
            $tenantId = auth()->user()->tenant_id;
            $users = $this->userService->getUsersByTenant($request, $tenantId);
            
            return ApiResponseClass::sendResponse([
                'users' => UserResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ]
            ], 'Usuários recuperados com sucesso');
            
        } catch (\Exception $e) {
            Log::error('Erro ao listar usuários: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar usuários');
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        try {
            $this->authorizeOrFail('users.create');
            
            DB::beginTransaction();
            
            $validatedData = $request->validated();
            $validatedData['tenant_id'] = auth()->user()->tenant_id;
            $validatedData['password'] = Hash::make($validatedData['password']);
            
            $user = User::create($validatedData);
            
            // Sync profiles if provided
            if (isset($validatedData['profiles'])) {
                $user->profiles()->sync($validatedData['profiles']);
            }
            
            DB::commit();
            
            // Invalidate user cache
            $this->userService->invalidateUserCache(auth()->user()->tenant_id);
            
            return ApiResponseClass::sendResponse(
                new UserResource($user->load(['profiles', 'tenant'])),
                'Usuário criado com sucesso',
                201
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao criar usuário');
        }
    }

    /**
     * Display the specified user.
     */
    public function show($id): JsonResponse
    {
        try {
            $this->authorizeOrFail('users.show');
            
            $user = User::find($id);
            
            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            // Verificar se o usuário pertence ao mesmo tenant
            if ($user->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            return ApiResponseClass::sendResponse(
                new UserResource($user->load(['profiles', 'tenant'])),
                'Usuário recuperado com sucesso'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar usuário');
        }
    }

    /**
     * Update the specified user.
     */
    public function update(UserUpdateRequest $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            // Verificar se o usuário pertence ao mesmo tenant
            if ($user->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            DB::beginTransaction();
            
            $validatedData = $request->validated();
            
            // Hash password if provided
            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }
            
            $user->update($validatedData);
            
            // Sync profiles if provided
            if (isset($validatedData['profiles'])) {
                $user->profiles()->sync($validatedData['profiles']);
            }
            
            DB::commit();
            
            // Invalidate user cache
            $this->userService->invalidateUserCache(auth()->user()->tenant_id);
            
            return ApiResponseClass::sendResponse(
                new UserResource($user->load(['profiles', 'tenant'])),
                'Usuário atualizado com sucesso'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao atualizar usuário');
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            // Verificar se o usuário pertence ao mesmo tenant
            if ($user->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            // Não permitir deletar o próprio usuário
            if ($user->id === auth()->id()) {
                return ApiResponseClass::sendResponse(null, 'Não é possível deletar seu próprio usuário', 400);
            }
            
            $user->delete();
            
            // Invalidate user cache
            $this->userService->invalidateUserCache(auth()->user()->tenant_id);
            
            return ApiResponseClass::sendResponse(null, 'Usuário excluído com sucesso');
            
        } catch (\Exception $e) {
            Log::error('Erro ao excluir usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao excluir usuário');
        }
    }

    /**
     * Assign profile to user.
     */
    public function assignProfile(UserAssignProfileRequest $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            // Verificar se o usuário pertence ao mesmo tenant
            if ($user->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            $validatedData = $request->validated();
            $profile = Profile::find($validatedData['profile_id']);
            
            if (!$profile || $profile->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }
            
            $user->profiles()->syncWithoutDetaching([$validatedData['profile_id']]);
            
            return ApiResponseClass::sendResponse(
                new UserResource($user->load(['profiles', 'tenant'])),
                'Perfil atribuído com sucesso'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro ao atribuir perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao atribuir perfil');
        }
    }

    /**
     * Change user password.
     */
    public function changePassword(UserChangePasswordRequest $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            // Verificar se o usuário pertence ao mesmo tenant
            if ($user->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            $validatedData = $request->validated();
            $user->update(['password' => Hash::make($validatedData['password'])]);
            
            return ApiResponseClass::sendResponse(null, 'Senha alterada com sucesso');
            
        } catch (\Exception $e) {
            Log::error('Erro ao alterar senha: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao alterar senha');
        }
    }

    /**
     * Get user permissions.
     */
    public function getUserPermissions($id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            // Verificar se o usuário pertence ao mesmo tenant
            if ($user->tenant_id !== auth()->user()->tenant_id) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }
            
            $permissions = $user->profiles()
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->unique('id');
            
            return ApiResponseClass::sendResponse($permissions, 'Permissões do usuário recuperadas com sucesso');
            
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar permissões do usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar permissões do usuário');
        }
    }
}
