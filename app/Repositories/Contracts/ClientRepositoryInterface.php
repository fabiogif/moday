<?php

namespace App\Repositories\Contracts;

interface ClientRepositoryInterface extends BaseRepositoryInterface
{
    public function createClient(array $data);
    public function getTableByIdentify(string $identify);
    public function getAllClients();
    public function getClientsByTenant($tenantId);
    public function getClientById($id);
    public function getClientByUuid($uuid);
    public function updateClient($id, array $data);
    public function deleteClient($id);
    public function findByCpfAndTenant(string $cpf, int $tenantId);

    public function findByEmailAndTenant(string $email, int $tenantId);

    public function findByPhoneAndTenant(string $phone, int $tenantId);

    public function emailExistsForTenant(string $email, int $tenantId): bool;

    public function cpfExistsForOtherClient(string $cpf, int $tenantId, int $excludeId): bool;

    public function emailExistsForOtherClient(string $email, int $tenantId, int $excludeId): bool;

    public function registerAuthClient(array $data, int $tenantId);

    public function createPublicClient(array $data);
}
