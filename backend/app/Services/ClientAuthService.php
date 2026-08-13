<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Tenant;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientAuthService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function findActiveTenantBySlug(string $slug): ?Tenant
    {
        return $this->tenantRepository->findActiveBySlug($slug);
    }

    public function emailExistsForTenant(string $email, int $tenantId): bool
    {
        return $this->clientRepository->emailExistsForTenant($email, $tenantId);
    }

    public function registerClient(array $data, Tenant $tenant): array
    {
        $client = $this->clientRepository->registerAuthClient($data, $tenant->id);
        $token = JWTAuth::fromUser($client);

        return [$client, $token];
    }

    public function authenticate(string $email, string $password, Tenant $tenant): ?Client
    {
        $client = $this->clientRepository->findByEmailAndTenant($email, $tenant->id);

        if (!$client) {
            return null;
        }

        if (!Hash::check($password, $client->password ?? '')) {
            return null;
        }

        if (!$client->is_active) {
            return null;
        }

        return $client;
    }

    public function generateTokenForClient(Client $client): string
    {
        return JWTAuth::fromUser($client);
    }

    public function getClientOrders(Client $client)
    {
        return $this->orderRepository->getOrdersByClientIdWithRelations($client->id);
    }
}
