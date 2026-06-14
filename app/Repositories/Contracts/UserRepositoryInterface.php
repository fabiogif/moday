<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function createUser(array $data);
    public function getUsersByTenant($tenantId, $filters = [], $perPage = 15);
    public function getUserByUuid($uuid);
    public function getUserByEmail($email);
    public function updateUser($id, array $data);
    public function deleteUser($id);
    public function getUserById($id);

    public function countActiveUsersByTenant(int $tenantId): int;

    public function paginateForTenant(int $tenantId, array $params): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function getStatsForTenant(int $tenantId): array;

    public function findProfileByIdAndTenant(int $profileId, int $tenantId): ?\App\Models\Profile;

    public function syncProfiles(\App\Models\User $user, array $profileIds): void;

    public function attachProfile(\App\Models\User $user, int $profileId): void;

    public function getUserPermissions(\App\Models\User $user): mixed;

    public function loadRelations(\App\Models\User $user, array $relations): \App\Models\User;
}