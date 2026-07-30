<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PermissionProfileService
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function getProfilePermissions(int $profileId, int $tenantId)
    {
        return $this->profileRepository->getPermissionsForProfile($profileId, $tenantId);
    }

    public function getAvailablePermissionsForProfile(int $profileId, int $tenantId)
    {
        return $this->profileRepository->getAvailablePermissionsForProfile($profileId, $tenantId);
    }

    public function attachPermissionToProfile(int $profileId, int $permissionId, int $tenantId): ?Profile
    {
        $profile = $this->profileRepository->attachPermission($profileId, $permissionId, $tenantId);
        $this->clearProfileUsersPermissionCache($profile);

        return $profile;
    }

    public function detachPermissionFromProfile(int $profileId, int $permissionId, int $tenantId): ?Profile
    {
        $profile = $this->profileRepository->detachPermission($profileId, $permissionId, $tenantId);
        $this->clearProfileUsersPermissionCache($profile);

        return $profile;
    }

    public function syncPermissionsForProfile(int $profileId, array $permissionIds, int $tenantId): ?Profile
    {
        $count = $this->permissionRepository->countByIdsForTenant($permissionIds, $tenantId);
        if ($count !== count($permissionIds)) {
            throw new \InvalidArgumentException('invalid_permissions');
        }

        $profile = DB::transaction(
            fn () => $this->profileRepository->syncPermissions($profileId, $permissionIds, $tenantId)
        );
        $this->clearProfileUsersPermissionCache($profile);

        return $profile;
    }

    public function getPermissionProfiles(int $permissionId, int $tenantId)
    {
        return $this->profileRepository->getProfilesForPermission($permissionId, $tenantId);
    }

    private function clearProfileUsersPermissionCache(?Profile $profile): void
    {
        if (!$profile) {
            return;
        }

        $userIds = $profile->users()->pluck('users.id')
            ->merge(User::query()->where('profile_id', $profile->id)->pluck('id'))
            ->unique()
            ->values();

        User::query()
            ->whereIn('id', $userIds)
            ->eachById(fn (User $user) => $user->clearPermissionsCache());
    }
}
