<?php

namespace App\Services;

use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

readonly class ProfileService
{
    public function __construct(
        private ProfileRepositoryInterface $profileRepository
    ) {}

    public function listForCurrentTenant(array $filters = []): ?Collection
    {
        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId) {
            return null;
        }

        return $this->profileRepository->listByTenant($tenantId, $filters);
    }

    public function createForCurrentTenant(array $data): ?Profile
    {
        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId) {
            return null;
        }

        return $this->profileRepository->createForTenant($data, $tenantId);
    }

    public function findForCurrentTenant(int $profileId): ?Profile
    {
        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId) {
            return null;
        }

        return $this->profileRepository->findForTenant($profileId, $tenantId);
    }

    public function updateForCurrentTenant(Profile $profile, array $data): ?Profile
    {
        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId || $profile->tenant_id !== $tenantId) {
            return null;
        }

        return $this->profileRepository->updateProfile($profile, $data);
    }

    public function deleteForCurrentTenant(Profile $profile): array
    {
        $tenantId = Auth::user()?->tenant_id;
        if (!$tenantId || $profile->tenant_id !== $tenantId) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        $usersCount = $this->profileRepository->countUsers($profile);
        if ($usersCount > 0) {
            return ['success' => false, 'reason' => 'in_use', 'users_count' => $usersCount];
        }

        $this->profileRepository->deleteProfile($profile);

        return ['success' => true];
    }
}
