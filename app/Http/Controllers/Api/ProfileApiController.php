<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Http\Requests\ProfileStoreRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\ProfileResource;
use App\Classes\ApiResponseClass;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileApiController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $profiles = $this->profileService->listForCurrentTenant(
                $request->only(['name', 'description', 'is_active'])
            );

            return ApiResponseClass::sendResponse([
                'profiles' => ProfileResource::collection($profiles ?? collect()),
            ], 'Perfis recuperados com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao listar perfis: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar perfis');
        }
    }

    public function store(ProfileStoreRequest $request): JsonResponse
    {
        try {
            $profile = $this->profileService->createForCurrentTenant($request->validated());

            return ApiResponseClass::sendResponse(
                new ProfileResource($profile),
                'Perfil criado com sucesso',
                201
            );
        } catch (\Exception $e) {
            Log::error('Erro ao criar perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao criar perfil');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $found = $this->profileService->findForCurrentTenant($id);
            if (!$found) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new ProfileResource($found->load(['permissions', 'tenant'])),
                'Perfil recuperado com sucesso'
            );
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao recuperar perfil');
        }
    }

    public function update(ProfileUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $profile = $this->profileService->findForCurrentTenant($id);
            if (!$profile) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }

            $updated = $this->profileService->updateForCurrentTenant($profile, $request->validated());
            if (!$updated) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new ProfileResource($updated),
                'Perfil atualizado com sucesso'
            );
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao atualizar perfil');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $profile = $this->profileService->findForCurrentTenant($id);
            if (!$profile) {
                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }

            $result = $this->profileService->deleteForCurrentTenant($profile);

            if (!$result['success']) {
                if (($result['reason'] ?? '') === 'in_use') {
                    return ApiResponseClass::sendResponse(
                        null,
                        "Não é possível excluir o perfil. Ele está sendo usado por {$result['users_count']} usuário(s).",
                        400
                    );
                }

                return ApiResponseClass::sendResponse(null, 'Perfil não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(null, 'Perfil excluído com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir perfil: ' . $e->getMessage());
            return ApiResponseClass::throw($e, 'Erro ao excluir perfil');
        }
    }
}
