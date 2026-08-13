<?php

namespace App\Repositories\Contracts;

use App\Models\Profile;
use Illuminate\Support\Collection;

interface ProfileRepositoryInterface
{
    public function listByTenant(int $tenantId, array $filters = []): Collection;

    public function createForTenant(array $data, int $tenantId): Profile;

    public function findForTenant(int $profileId, int $tenantId): ?Profile;

    public function updateProfile(Profile $profile, array $data): Profile;

    public function deleteProfile(Profile $profile): bool;

    public function countUsers(Profile $profile): int;

    public function findByName(string $name): ?Profile;

    public function getPermissionsForProfile(int $profileId, int $tenantId): ?\Illuminate\Support\Collection;

    public function getAvailablePermissionsForProfile(int $profileId, int $tenantId): ?\Illuminate\Support\Collection;

    public function attachPermission(int $profileId, int $permissionId, int $tenantId): ?Profile;

    public function detachPermission(int $profileId, int $permissionId, int $tenantId): ?Profile;

    public function syncPermissions(int $profileId, array $permissionIds, int $tenantId): ?Profile;

    public function getProfilesForPermission(int $permissionId, int $tenantId): ?\Illuminate\Support\Collection;
}
