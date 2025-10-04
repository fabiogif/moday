<?php

namespace App\Repositories\contracts;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function createUser(array $data);
    public function getUsersByTenant($tenantId, $filters = [], $perPage = 15);
    public function getUserByUuid($uuid);
    public function getUserByEmail($email);
    public function updateUser($id, array $data);
    public function deleteUser($id);
    public function getUserById($id);
}