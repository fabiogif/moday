<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Tenant;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicClientService
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    /**
     * Create or update client for public store
     */
    public function createOrUpdateClient(array $clientData, Tenant $tenant, ?array $delivery = null): Client
    {
        $cpf = $this->cleanCpf($clientData['cpf'] ?? null);
        $email = $clientData['email'] ?? null;

        Log::info('PublicClientService: Tentando criar/atualizar cliente', [
            'cpf' => $cpf,
            'email' => $email,
            'tenant_id' => $tenant->id,
            'name' => $clientData['name'] ?? null,
        ]);

        if ($cpf) {
            $existing = $this->clientRepository->findByCpfAndTenant($cpf, $tenant->id);
            if ($existing) {
                return $this->updateExistingClient($existing, $clientData, $email, false, $delivery);
            }
        }

        if ($email && !$cpf) {
            $existing = $this->clientRepository->findByEmailAndTenant($email, $tenant->id);
            if ($existing) {
                return $this->updateExistingClient($existing, $clientData, $email, true, $delivery);
            }
        }

        if ($phone = $this->cleanPhone($clientData['phone'] ?? null)) {
            $existing = $this->clientRepository->findByPhoneAndTenant($phone, $tenant->id);
            if ($existing) {
                return $this->updateExistingClient($existing, $clientData, $email, false, $delivery);
            }
        }

        return $this->createNewClient($clientData, $tenant, $cpf, $email, $delivery);
    }

    private function updateExistingClient(
        Client $existing,
        array $clientData,
        ?string $email,
        bool $foundByEmail = false,
        ?array $delivery = null
    ): Client {
        Log::info('PublicClientService: Cliente encontrado, atualizando', [
            'client_id' => $existing->id,
            'found_by' => $foundByEmail ? 'email' : 'cpf',
        ]);

        // Pedido sem cadastro não altera dados já preenchidos do cliente (qualquer um pode digitar
        // o CPF/e-mail/telefone de outra pessoa). Só completa campos vazios; o contato digitado
        // fica gravado no próprio pedido (orders.customer_*).
        $updateData = ['is_active' => true];

        $candidates = array_merge([
            'name' => $clientData['name'] ?? null,
            'phone' => $this->cleanPhone($clientData['phone'] ?? null),
        ], $this->extractAddressFieldsFromDelivery($delivery));

        foreach ($candidates as $field => $value) {
            if (filled($value) && blank($existing->{$field})) {
                $updateData[$field] = $value;
            }
        }

        $cleanCpf = $this->cleanCpf($clientData['cpf'] ?? null);
        if ($cleanCpf && blank($existing->cpf)
            && !$this->clientRepository->cpfExistsForOtherClient($cleanCpf, $existing->tenant_id, $existing->id)) {
            $updateData['cpf'] = $cleanCpf;
        }

        if ($email && blank($existing->email)
            && !$this->clientRepository->emailExistsForOtherClient($email, $existing->tenant_id, $existing->id)) {
            $updateData['email'] = $email;
        }

        $updated = $this->clientRepository->updateClient($existing->id, $updateData);

        Log::info('PublicClientService: Cliente atualizado com sucesso', [
            'client_id' => $existing->id,
        ]);

        return $updated;
    }

    private function createNewClient(array $clientData, Tenant $tenant, ?string $cpf, ?string $email, ?array $delivery = null): Client
    {
        Log::info('PublicClientService: Criando novo cliente', [
            'cpf' => $cpf,
            'email' => $email,
            'tenant_id' => $tenant->id,
        ]);

        if ($email && $this->clientRepository->emailExistsForTenant($email, $tenant->id)) {
            Log::warning('PublicClientService: Email já existe, buscando cliente por email');
            $existing = $this->clientRepository->findByEmailAndTenant($email, $tenant->id);

            return $this->updateExistingClient($existing, $clientData, $email, true, $delivery);
        }

        $newClient = $this->clientRepository->createPublicClient(array_merge([
            'uuid' => Str::uuid(),
            'name' => $clientData['name'],
            'email' => $email,
            'phone' => $this->cleanPhone($clientData['phone'] ?? null),
            'cpf' => $cpf,
            'is_active' => true,
            'tenant_id' => $tenant->id,
        ], $this->extractAddressFieldsFromDelivery($delivery)));

        Log::info('PublicClientService: Cliente criado com sucesso', [
            'client_id' => $newClient->id,
            'uuid' => $newClient->uuid,
            'cpf' => $newClient->cpf,
            'email' => $newClient->email,
        ]);

        return $newClient;
    }

    private function cleanCpf(?string $cpf): ?string
    {
        if (!$cpf) {
            return null;
        }

        return preg_replace('/[^0-9]/', '', $cpf);
    }

    private function cleanPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $clean = preg_replace('/\D/', '', $phone);

        return strlen($clean) >= 10 ? $clean : null;
    }

    private function extractAddressFieldsFromDelivery(?array $delivery): array
    {
        if (!$delivery || empty($delivery['is_delivery'])) {
            return [];
        }

        $fields = [];
        $map = [
            'address' => 'address',
            'number' => 'number',
            'neighborhood' => 'neighborhood',
            'city' => 'city',
            'state' => 'state',
            'zip_code' => 'zip_code',
            'complement' => 'complement',
        ];

        foreach ($map as $deliveryKey => $clientKey) {
            if (!empty($delivery[$deliveryKey])) {
                $fields[$clientKey] = $delivery[$deliveryKey];
            }
        }

        return $fields;
    }
}
