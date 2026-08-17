<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\UpdateAdminProfileRequest;
use App\Http\Requests\Api\Admin\UpdateAdminPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminProfileController extends Controller
{
    public function update(UpdateAdminProfileRequest $request): JsonResponse
    {
        try {
            $admin = $request->user('admin');

            $admin->update($request->validated());

            Log::info('AdminProfileController update', [
                'admin_id' => $admin->id,
                'ip' => $request->ip(),
            ]);

            return ApiResponseClass::sendResponse([
                'id'            => $admin->id,
                'name'          => $admin->name,
                'email'         => $admin->email,
                'role'          => $admin->role,
                'is_active'     => $admin->is_active,
                'last_login_at' => $admin->last_login_at,
            ], 'Perfil atualizado com sucesso.');
        } catch (\Exception $e) {
            Log::error('AdminProfileController update error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return ApiResponseClass::throw($e, 'Erro ao atualizar perfil');
        }
    }

    public function updatePassword(UpdateAdminPasswordRequest $request): JsonResponse
    {
        try {
            $admin = $request->user('admin');

            if (!Hash::check($request->current_password, $admin->password)) {
                return ApiResponseClass::validationError(
                    ['current_password' => ['A senha atual está incorreta.']],
                    'A senha atual está incorreta.'
                );
            }

            $admin->update(['password' => Hash::make($request->password)]);

            // Revoga todos os tokens existentes para forçar novo login
            $admin->tokens()->delete();

            Log::info('AdminProfileController updatePassword', [
                'admin_id' => $admin->id,
                'ip'       => $request->ip(),
            ]);

            return ApiResponseClass::sendResponse([], 'Senha alterada com sucesso. Faça login novamente.');
        } catch (\Exception $e) {
            Log::error('AdminProfileController updatePassword error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return ApiResponseClass::throw($e, 'Erro ao alterar senha');
        }
    }
}
