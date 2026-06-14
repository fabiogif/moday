<?php

namespace App\Http\Controllers\Api\Integrations\Ifood;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\IfoodTokenRepositoryInterface;
use App\Services\Integrations\Ifood\IfoodTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class IfoodAuthController extends Controller
{
    public function __construct(
        private readonly IfoodTokenService $tokenService,
        private readonly IfoodTokenRepositoryInterface $tokenRepository,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->resolveTenantId($request);

            $token = $this->tokenService->generateAndStoreToken($tenantId);

            return ApiResponseClass::sendResponse(
                $this->formatResponse($token->access_token, $token->expires_at, $token->token_type, $token->scope),
                'Token iFood gerado com sucesso',
                Response::HTTP_CREATED
            );
        } catch (\InvalidArgumentException $exception) {
            return ApiResponseClass::sendResponse('', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            return ApiResponseClass::throw($exception, 'Erro ao gerar token do iFood');
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->resolveTenantId($request);
            $current = $this->tokenRepository->getActiveToken($tenantId);

            if (!$current) {
                return ApiResponseClass::sendResponse('', 'Nenhum token ativo encontrado para renovação.', Response::HTTP_NOT_FOUND);
            }

            $token = $this->tokenService->refreshAndStoreToken($tenantId, $current)
                ?? $this->tokenService->generateAndStoreToken($tenantId);

            return ApiResponseClass::sendResponse(
                $this->formatResponse($token->access_token, $token->expires_at, $token->token_type, $token->scope),
                'Token iFood renovado com sucesso',
                Response::HTTP_OK
            );
        } catch (\InvalidArgumentException $exception) {
            return ApiResponseClass::sendResponse('', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            return ApiResponseClass::throw($exception, 'Erro ao renovar token do iFood');
        }
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->resolveTenantId($request);

            $token = $this->tokenRepository->getActiveToken($tenantId);

            if (!$token) {
                return ApiResponseClass::sendResponse('', 'Nenhum token ativo encontrado.', Response::HTTP_NOT_FOUND);
            }

            return ApiResponseClass::sendResponse(
                $this->formatResponse($token->access_token, $token->expires_at, $token->token_type, $token->scope),
                'Token iFood recuperado com sucesso',
                Response::HTTP_OK
            );
        } catch (\InvalidArgumentException $exception) {
            return ApiResponseClass::sendResponse('', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            return ApiResponseClass::throw($exception, 'Erro ao consultar token do iFood');
        }
    }

    private function resolveTenantId(Request $request): int
    {
        $tenantId = $request->integer('tenant_id')
            ?? $request->header('X-Tenant-Id')
            ?? Auth::user()?->tenant_id;

        if (!$tenantId) {
            throw new \InvalidArgumentException('tenant_id não informado.');
        }

        return (int) $tenantId;
    }

    private function presentToken(string $encryptedToken): string
    {
        $token = Crypt::decryptString($encryptedToken);

        return substr($token, 0, 4) . str_repeat('*', max(strlen($token) - 8, 4)) . substr($token, -4);
    }

    private function formatResponse(string $encryptedToken, $expiresAt, string $tokenType, ?string $scope): array
    {
        return [
            'token' => $this->presentToken($encryptedToken),
            'expires_at' => $expiresAt,
            'token_type' => $tokenType,
            'scope' => $scope,
        ];
    }
}

