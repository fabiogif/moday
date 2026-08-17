<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TenantRegisterRequest;
use App\Services\PlanService;
use App\Services\TenantRegistrationService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        protected TenantRegistrationService $tenantRegistrationService,
        protected PlanService $planService,
    ) {}

    public function register(TenantRegisterRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->tenantRegistrationService->register($data);

            $user = $result['user'];
            $tenant = $result['tenant'];
            $token = $result['token'];

            return ApiResponseClass::sendResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'email_verified' => false,
                    'email_verified_at' => null,
                    'tenant' => [
                        'id' => $tenant->id,
                        'uuid' => $tenant->uuid,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                    ],
                ],
                'token' => $token,
                'expires_in' => config('jwt.ttl', 120) * 60,
                'trial_status' => $result['trial_status'],
                'email_verified' => false,
                'requires_email_verification' => true,
            ], 'Cadastro realizado com sucesso! Confirme seu e-mail para continuar.', 201);
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao realizar cadastro. Por favor, tente novamente.');
        }
    }

    public function plans(): JsonResponse
    {
        try {
            $plans = $this->planService->getActivePlansWithDetails();

            return ApiResponseClass::sendResponse($plans, 'Planos carregados com sucesso');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao carregar planos');
        }
    }
}
