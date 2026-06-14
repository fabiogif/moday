<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\AdminLoginRequest;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AdminAuthService $authService
    ) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();
            $result = $this->authService->attemptLogin(
                $credentials['email'],
                $credentials['password'],
                $request->ip()
            );

            if (!$result['success']) {
                return match ($result['reason']) {
                    'inactive' => ApiResponseClass::forbidden('Sua conta está inativa. Entre em contato com o suporte.'),
                    default => ApiResponseClass::validationError(
                        ['email' => ['As credenciais fornecidas estão incorretas.']],
                        'As credenciais fornecidas estão incorretas.'
                    ),
                };
            }

            return ApiResponseClass::sendResponse([
                'admin' => $result['admin'],
                'token' => $result['token'],
            ], 'Login realizado com sucesso.');
        } catch (\Exception $e) {
            Log::error('AdminAuthController internal error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'request_id' => uniqid('admin_auth_'),
            ]);

            return ApiResponseClass::throw($e, 'Erro ao realizar login');
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $admin = $request->user('admin');

            if (!$admin) {
                return ApiResponseClass::unauthorized('Não autorizado.');
            }

            $this->authService->logout($admin);

            return ApiResponseClass::sendResponse('', 'Logout realizado com sucesso.');
        } catch (\Exception $e) {
            Log::error('AdminAuthController logout error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponseClass::throw($e, 'Erro ao realizar logout');
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $admin = $request->user('admin');

            if (!$admin) {
                return ApiResponseClass::unauthorized('Não autorizado.');
            }

            return ApiResponseClass::sendResponse(
                $this->authService->getMeData($admin),
                'Dados do administrador recuperados com sucesso.'
            );
        } catch (\Exception $e) {
            Log::error('AdminAuthController me error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponseClass::throw($e, 'Erro ao buscar dados do administrador');
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $admin = $request->user('admin');

            if (!$admin) {
                return ApiResponseClass::unauthorized('Não autorizado.');
            }

            $newToken = $this->authService->refreshToken($admin);

            return ApiResponseClass::sendResponse([
                'token' => $newToken,
            ], 'Token renovado com sucesso.');
        } catch (\Exception $e) {
            Log::error('AdminAuthController refresh error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponseClass::throw($e, 'Erro ao renovar token');
        }
    }
}
