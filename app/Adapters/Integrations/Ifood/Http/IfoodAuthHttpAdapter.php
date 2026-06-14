<?php

namespace App\Adapters\Integrations\Ifood\Http;

use App\Ports\Integrations\Ifood\IfoodAuthPort;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IfoodAuthHttpAdapter implements IfoodAuthPort
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $oauthUrl,
        private readonly ?string $scope = null,
    ) {
    }

    public static function makeFromConfig(): self
    {
        $config = config('services.ifood');

        if (!$config || empty($config['client_id']) || empty($config['client_secret']) || empty($config['oauth_url'])) {
            throw new RuntimeException('Configuração do iFood incompleta.');
        }

        return new self(
            clientId: $config['client_id'],
            clientSecret: $config['client_secret'],
            oauthUrl: rtrim($config['oauth_url'], '/'),
            scope: $config['scope'] ?? null,
        );
    }

    public function generateToken(): array
    {
        try {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->acceptJson()
                ->post($this->oauthUrl . '/token', array_filter([
                    'grant_type' => 'client_credentials',
                    'scope' => $this->scope,
                ]));

            $response->throw();

            return $response->json();
        } catch (RequestException $exception) {
            Log::channel('ifood')->error('Erro ao gerar token iFood', [
                'exception' => $exception->getMessage(),
                'response' => $exception->response?->json(),
            ]);

            throw $exception;
        }
    }

    public function refreshToken(array $payload): array
    {
        if (empty($payload['refresh_token'])) {
            throw new RuntimeException('Refresh token não informado.');
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->acceptJson()
                ->post($this->oauthUrl . '/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $payload['refresh_token'],
                ]);

            $response->throw();

            return $response->json();
        } catch (RequestException $exception) {
            Log::channel('ifood')->error('Erro ao renovar token iFood', [
                'exception' => $exception->getMessage(),
                'response' => $exception->response?->json(),
            ]);

            throw $exception;
        }
    }

    public function requestUserCode(string $clientId): array
    {
        $url = rtrim($this->oauthUrl, '/') . '/oauth/userCode';

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->post($url, [
                    'clientId' => $clientId,
                ]);

            $response->throw();

            return $response->json();
        } catch (RequestException $exception) {
            Log::channel('ifood')->error('Erro ao solicitar user code iFood', [
                'exception' => $exception->getMessage(),
                'response' => $exception->response?->json(),
            ]);

            throw $exception;
        }
    }
}

