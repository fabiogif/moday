<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    /**
     * Verifica se o usuário tem uma permissão específica
     */
    protected function checkPermission(string $permission, $resource = null): bool
    {
        $user = request()->user();
        return $user ? $user->hasPermission($permission) : false;
    }

    /**
     * Autoriza ou falha com erro 403
     */
    protected function authorizeOrFail(string $permission, $resource = null): void
    {
        if (!$this->checkPermission($permission, $resource)) {
            abort(403, 'Ação não autorizada.');
        }
    }

    /**
     * Verifica se o usuário pode acessar um recurso específico
     */
    protected function canAccessResource(string $permission, $resource = null): bool
    {
        $user = request()->user();
        
        if (!$user) {
            return false;
        }

        // Verificação básica de permissão
        if (!$user->hasPermission($permission)) {
            return false;
        }

        // Verificação adicional de propriedade do recurso (se aplicável)
        if ($resource && method_exists($resource, 'tenant_id')) {
            return $user->tenant_id === $resource->tenant_id;
        }

        return true;
    }

    /**
     * Autoriza acesso a um recurso específico
     */
    protected function authorizeResourceAccess(string $permission, $resource = null): void
    {
        if (!$this->canAccessResource($permission, $resource)) {
            abort(403, 'Acesso negado ao recurso.');
        }
    }

    /**
     * Verifica se o usuário é admin
     */
    protected function isAdmin(): bool
    {
        $user = request()->user();
        return $user ? $user->isAdmin() : false;
    }

    /**
     * Verifica se o usuário pode gerenciar outros usuários
     */
    protected function canManageUsers(): bool
    {
        $user = request()->user();
        return $user ? $user->canManageUsers() : false;
    }

    /**
     * Verifica se o usuário pode acessar o admin
     */
    protected function canAccessAdmin(): bool
    {
        $user = request()->user();
        return $user ? $user->canAccessAdmin() : false;
    }

    /**
     * Obtém o tenant_id do usuário autenticado
     */
    protected function getCurrentTenantId(): ?int
    {
        $user = request()->user();
        return $user ? $user->tenant_id : null;
    }

    /**
     * Verifica se o usuário pertence ao mesmo tenant do recurso
     */
    protected function belongsToSameTenant($resource): bool
    {
        $user = request()->user();
        
        if (!$user || !$resource) {
            return false;
        }

        if (method_exists($resource, 'tenant_id')) {
            return $user->tenant_id === $resource->tenant_id;
        }

        return true;
    }

    /**
     * Log de verificação de permissão para debug
     */
    protected function logPermissionCheck(string $permission, bool $result, $context = []): void
    {
        if (config('app.debug', false)) {
            Log::debug('Permission check', array_merge([
                'user_id' => request()->user()?->id,
                'permission' => $permission,
                'result' => $result,
            ], $context));
        }
    }

    /**
     * Resposta de sucesso padronizada
     */
    protected function successResponse($data = null, string $message = 'Operação realizada com sucesso', int $code = 200): JsonResponse
    {
        return ApiResponseClass::sendResponse($data, $message, $code);
    }

    /**
     * Resposta de erro padronizada
     */
    protected function errorResponse(string $message = 'Erro na operação', int $code = 400, $errors = null): JsonResponse
    {
        $response = ['message' => $message];
        
        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Resposta de não autorizado
     */
    protected function unauthorizedResponse(string $message = 'Acesso negado'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Resposta de recurso não encontrado
     */
    protected function notFoundResponse(string $message = 'Recurso não encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Resposta de validação
     */
    protected function validationResponse($errors, string $message = 'Dados inválidos'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }
}
