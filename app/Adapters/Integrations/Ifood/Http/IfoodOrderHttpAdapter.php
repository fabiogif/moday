<?php

namespace App\Adapters\Integrations\Ifood\Http;

use App\DTO\Integrations\Ifood\IfoodOrderStatusDTO;
use App\Ports\Integrations\Ifood\IfoodOrderPort;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IfoodOrderHttpAdapter implements IfoodOrderPort
{
    public function __construct(
        private readonly string $baseUrl,
    ) {
    }

    public static function makeFromConfig(): self
    {
        $config = config('services.ifood');

        if (!$config || empty($config['base_url'])) {
            throw new RuntimeException('Configuração de base_url do iFood ausente.');
        }

        return new self(
            baseUrl: rtrim($config['base_url'], '/'),
        );
    }

    public function sendStatus(string $accessToken, IfoodOrderStatusDTO $dto): void
    {
        $payload = [
            'statuses' => [
                [
                    'status' => $dto->status,
                    'createdAt' => now()->toIso8601String(),
                    'reason' => $dto->reason,
                    'description' => $dto->description,
                ],
            ],
        ];

        $this->request(
            accessToken: $accessToken,
            method: 'post',
            uri: "/order/v1.0/orders/{$dto->externalOrderId}/statuses",
            payload: $payload
        );
    }

    public function confirm(string $accessToken, string $externalOrderId): void
    {
        $this->request(
            accessToken: $accessToken,
            method: 'post',
            uri: '/order/v1.0/orders/acknowledgment',
            payload: [
                [
                    'id' => $externalOrderId,
                    'status' => 'CONFIRMED',
                    'createdAt' => now()->toIso8601String(),
                ],
            ]
        );
    }

    public function reject(string $accessToken, string $externalOrderId, ?string $reason = null): void
    {
        $payload = [
            [
                'id' => $externalOrderId,
                'status' => 'CANCELLED',
                'createdAt' => now()->toIso8601String(),
                'reason' => $reason ?? 'UNAVAILABLE',
            ],
        ];

        $this->request(
            accessToken: $accessToken,
            method: 'post',
            uri: '/order/v1.0/orders/cancellation',
            payload: $payload
        );
    }

    private function request(string $accessToken, string $method, string $uri, array $payload = []): void
    {
        $url = $this->baseUrl . $uri;

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->withHeaders([
                    'Merchant-Id' => config('services.ifood.merchant_id'),
                ])
                ->{$method}($url, $payload);

            $response->throw();
        } catch (RequestException $exception) {
            Log::channel('ifood')->error('Erro na requisição iFood', [
                'url' => $url,
                'payload' => $payload,
                'exception' => $exception->getMessage(),
                'response' => $exception->response?->json(),
            ]);

            throw $exception;
        }
    }
}

