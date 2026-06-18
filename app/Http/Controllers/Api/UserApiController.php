<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UserAssignProfileRequest;
use App\Http\Requests\UserChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use App\Classes\ApiResponseClass;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserApiController extends BaseController
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            if (!Auth::check()) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }

            $tenantId = Auth::user()->tenant_id;
            $users = $this->userService->getUsersByTenant($request, $tenantId);

            return ApiResponseClass::sendResponse([
                'users' => UserResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ], 'Usuários recuperados com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao listar usuários: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar usuários');
        }
    }

    public function store(UserStoreRequest $request): JsonResponse
    {
        try {
            $this->authorizeOrFail('users.create');

            $validatedData = $request->validated();
            $user = $this->userService->createUserForCurrentTenant($validatedData);

            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }

            return ApiResponseClass::sendResponse(
                new UserResource($user),
                'Usuário criado com sucesso',
                201
            );
        } catch (UniqueConstraintViolationException $e) {
            return ApiResponseClass::sendResponse(null, 'Este email já está em uso por outro usuário.', 422);
        } catch (\Exception $e) {
            Log::error('Erro ao criar usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao criar usuário');
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $this->authorizeOrFail('users.show');

            $user = $this->userService->findUserForCurrentTenant((int) $id);

            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new UserResource($user->load(['profiles', 'tenant', 'jobPosition'])),
                'Usuário recuperado com sucesso'
            );
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar usuário');
        }
    }

    public function update(UserUpdateRequest $request, $id): JsonResponse
    {
        try {
            $user = $this->userService->findUserForCurrentTenant((int) $id);

            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }

            $validatedData = $request->validated();

            $updatedUser = $this->userService->updateUserForCurrentTenant($user, $validatedData);

            if (!$updatedUser) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new UserResource($updatedUser),
                'Usuário atualizado com sucesso'
            );
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao atualizar usuário');
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $user = $this->userService->findUserForCurrentTenant((int) $id);

            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }

            if (!$this->userService->deleteUserForCurrentTenant($user)) {
                return ApiResponseClass::sendResponse(null, 'Não é possível deletar seu próprio usuário', 400);
            }

            return ApiResponseClass::sendResponse(null, 'Usuário excluído com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao excluir usuário');
        }
    }

    public function assignProfile(UserAssignProfileRequest $request, $id): JsonResponse
    {
        try {
            $user = $this->userService->findUserForCurrentTenant((int) $id);

            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }

            $validatedData = $request->validated();
            $updatedUser = $this->userService->assignProfileForCurrentTenant($user, (int) $validatedData['profile_id']);

            if (!$updatedUser) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new UserResource($updatedUser),
                'Perfil atribuído com sucesso'
            );
        } catch (\Exception $e) {
            Log::error('Erro ao atribuir perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao atribuir perfil');
        }
    }

    public function changePassword(UserChangePasswordRequest $request, $id): JsonResponse
    {
        try {
            $user = $this->userService->findUserForCurrentTenant((int) $id);

            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }

            $validatedData = $request->validated();
            $updatedUser = $this->userService->changePasswordForCurrentTenant($user, $validatedData['password']);

            if (!$updatedUser) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(null, 'Senha alterada com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao alterar senha: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao alterar senha');
        }
    }

    public function getUserPermissions($id): JsonResponse
    {
        try {
            $user = $this->userService->findUserForCurrentTenant((int) $id);

            if (!$user) {
                return ApiResponseClass::sendResponse(null, 'Usuário não encontrado', 404);
            }

            $permissions = $this->userService->getUserPermissionsForCurrentTenant($user);

            return ApiResponseClass::sendResponse($permissions, 'Permissões do usuário recuperadas com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar permissões do usuário: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar permissões do usuário');
        }
    }
}
