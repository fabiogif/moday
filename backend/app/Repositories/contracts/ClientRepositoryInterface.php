<?php

namespace App\Repositories\contracts;

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
}
