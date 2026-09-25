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

    public function dispatchOrder(string $accessToken, string $externalOrderId): void
    {
        $this->request(
            accessToken: $accessToken,
            method: 'post',
            uri: "/order/v1.0/orders/{$externalOrderId}/dispatch"
        );
    }

    public function readyToPickup(string $accessToken, string $externalOrderId): void
    {
        $this->request(
            accessToken: $accessToken,
            method: 'post',
            uri: "/order/v1.0/orders/{$externalOrderId}/readyToPickup"
        );
    }

    public function pollEvents(string $accessToken): array
    {
        $url = $this->baseUrl . '/order/v1.0/orders:polling';

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(10)
                ->get($url);

            if ($response->status() === 204) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable $exception) {
            Log::channel('ifood')->error('Erro ao consultar polling de eventos iFood', [
                'url' => $url,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    public function acknowledgeEvents(string $accessToken, array $eventIds): void
    {
        if (empty($eventIds)) {
            return;
        }

        $payload = array_map(function ($id) {
            return is_array($id) ? $id : ['id' => $id];
        }, $eventIds);

        $this->request(
            accessToken: $accessToken,
            method: 'post',
            uri: '/order/v1.0/orders:acknowledgment',
            payload: $payload
        );
    }

    public function getOrderDetails(string $accessToken, string $externalOrderId): array
    {
        $url = $this->baseUrl . "/order/v1.0/orders/{$externalOrderId}";

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(10)
            ->get($url);

        $response->throw();

        return $response->json() ?? [];
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

