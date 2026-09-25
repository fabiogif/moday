<?php

namespace App\Services\Integrations\Ifood;

use App\Adapters\Integrations\Ifood\Http\IfoodAuthHttpAdapter;
use App\Models\Integrations\Ifood\IfoodApiToken;
use App\Ports\Integrations\Ifood\IfoodAuthPort;
use App\Repositories\Contracts\IfoodTokenRepositoryInterface;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

class IfoodTokenService
{
    public function __construct(
        private readonly IfoodAuthPort $authPort,
        private readonly IfoodTokenRepositoryInterface $tokenRepository,
    ) {
    }

    public function getValidAccessToken(int $tenantId): string
    {
        $token = $this->tokenRepository->getActiveToken($tenantId);

        if (!$token) {
            $token = $this->generateAndStoreToken($tenantId);
        }

        if ($token->expires_at->lessThanOrEqualTo(now()->addMinutes(2))) {
            $token = $this->refreshAndStoreToken($tenantId, $token) ?? $this->generateAndStoreToken($tenantId);
        }

        return Crypt::decryptString($token->access_token);
    }

    public function generateAndStoreToken(int $tenantId): IfoodApiToken
    {
        $response = $this->authPort->generateToken();

        return $this->persistTokenResponse($tenantId, $response);
    }

    public function refreshAndStoreToken(int $tenantId, IfoodApiToken $token): ?IfoodApiToken
    {
        if (!$token->refresh_token) {
            return null;
        }

        try {
            $response = $this->authPort->refreshToken([
                'refresh_token' => Crypt::decryptString($token->refresh_token),
            ]);

            return $this->persistTokenResponse($tenantId, $response);
        } catch (Throwable $exception) {
            Log::channel('ifood')->warning('Falha ao renovar token iFood, tentando gerar um novo.', [
                'tenant_id' => $tenantId,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Persiste resposta do iFood aplicando criptografia aos tokens.
     */
    private function persistTokenResponse(int $tenantId, array $response): IfoodApiToken
    {
        $data = [
            'tenant_id' => $tenantId,
            'access_token' => Crypt::encryptString($response['access_token']),
            'refresh_token' => isset($response['refresh_token']) && $response['refresh_token']
                ? Crypt::encryptString($response['refresh_token'])
                : null,
            'token_type' => $response['token_type'] ?? 'Bearer',
            'scope' => $response['scope'] ?? null,
            'expires_at' => now()->addSeconds((int) $response['expires_in']),
            'metadata' => $response,
            'is_active' => true,
        ];

        return $this->tokenRepository->storeToken($data);
    }
}

