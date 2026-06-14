<?php

namespace App\Services\Integrations\Ifood;

use App\Ports\Integrations\Ifood\IfoodAuthPort;
use App\Repositories\Contracts\IfoodOauthSessionRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class IfoodOAuthService
{
    public function __construct(
        private readonly IfoodAuthPort $authPort,
        private readonly IfoodOauthSessionRepositoryInterface $sessionRepository,
    ) {
    }

    public function requestUserCode(int $tenantId): array
    {
        $clientId = Config::get('services.ifood.client_id');

        if (!$clientId) {
            throw ValidationException::withMessages([
                'client_id' => 'IFOOD_CLIENT_ID não configurado.',
            ]);
        }

        $payload = $this->authPort->requestUserCode($clientId);

        $expiresIn = (int) Arr::get($payload, 'expiresIn', 600);
        $expiresAt = now()->addSeconds($expiresIn);

        $this->sessionRepository->create([
            'tenant_id' => $tenantId,
            'user_code' => Arr::get($payload, 'userCode'),
            'authorization_code_verifier' => Arr::get($payload, 'authorizationCodeVerifier'),
            'verification_url' => Arr::get($payload, 'verificationUrl'),
            'verification_url_complete' => Arr::get($payload, 'verificationUrlComplete'),
            'expires_at' => $expiresAt,
        ]);

        Log::channel('ifood')->info('User code iFood solicitado com sucesso', [
            'tenant_id' => $tenantId,
            'user_code' => Arr::get($payload, 'userCode'),
            'expires_at' => $expiresAt,
        ]);

        return [
            'user_code' => Arr::get($payload, 'userCode'),
            'verification_url' => Arr::get($payload, 'verificationUrl'),
            'verification_url_complete' => Arr::get($payload, 'verificationUrlComplete'),
            'expires_in' => $expiresIn,
            'expires_at' => $expiresAt,
        ];
    }
}

