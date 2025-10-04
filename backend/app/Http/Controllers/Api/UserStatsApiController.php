<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Services\UserService;
use App\Classes\ApiResponseClass;
use Illuminate\Http\JsonResponse;

class UserStatsApiController extends BaseController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get user statistics
     */
    public function stats(): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return ApiResponseClass::sendResponse(null, 'Usuário não autenticado', 401);
            }

            $tenantId = auth()->user()->tenant_id;
            $stats = $this->userService->getUserStats($tenantId);

            return ApiResponseClass::sendResponse($stats, 'Estatísticas de usuários recuperadas com sucesso');

        } catch (\Exception $e) {
            return ApiResponseClass::throw($e, 'Erro ao recuperar estatísticas de usuários');
        }
    }
}

