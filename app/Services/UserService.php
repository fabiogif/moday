<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly ListingCacheService $listingCacheService,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function getUsersByTenant(Request $request, int $tenantId): LengthAwarePaginator
    {
        $perPage = min($request->get('per_page', 15), 100);
        $filters = $request->only(['name', 'email', 'status']);
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $cacheKey = "user_list_tenant_{$tenantId}_page_{$request->get('page', 1)}_per_page_{$perPage}";

        if ($search) {
            $cacheKey .= "_search_{$search}";
        }

        if ($sortBy !== 'created_at' || $sortDirection !== 'desc') {
            $cacheKey .= "_sort_{$sortBy}_{$sortDirection}";
        }

        foreach ($filters as $key => $value) {
            if ($value) {
                $cacheKey .= "_filter_{$key}_{$value}";
            }
        }

        return $this->listingCacheService->rememberPaginated(
            $cacheKey,
            'user_list',
            fn () => $this->userRepository->paginateForTenant($tenantId, [
                'per_page' => $perPage,
                'filters' => $filters,
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ])
        );
    }

    public function invalidateUserCache(int $tenantId): void
    {
        $this->listingCacheService->invalidateUserListCache($tenantId);
    }

    public function getUserStats(int $tenantId): array
    {
        $cacheKey = "user_stats_tenant_{$tenantId}";

        return Cache::remember($cacheKey, 15, fn () => $this->userRepository->getStatsForTenant($tenantId));
    }

    public function createUserForCurrentTenant(array $data): ?User
    {
        $authUser = Auth::user();

        if (!$authUser || !$authUser->tenant_id) {
            return null;
        }

        $data['tenant_id'] = $authUser->tenant_id;
        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepository->createUser($data);

        if (isset($data['profiles'])) {
            $this->userRepository->syncProfiles($user, $data['profiles']);
        }

        $this->invalidateUserCache((int) $authUser->tenant_id);

        return $user->load(['profiles', 'tenant', 'jobPosition']);
    }

    public function findUserForCurrentTenant(int $id): ?User
    {
        $authUser = Auth::user();

        if (!$authUser || !$authUser->tenant_id) {
            return null;
        }

        $user = $this->userRepository->getUserById($id);

        if (!$user || $user->tenant_id !== $authUser->tenant_id) {
            return null;
        }

        return $user;
    }

    public function updateUserForCurrentTenant(User $user, array $data): ?User
    {
        $authUser = Auth::user();

        if (!$authUser || !$authUser->tenant_id || $user->tenant_id !== $authUser->tenant_id) {
            return null;
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = $this->userRepository->updateUser($user->id, $data);

        if (isset($data['profiles'])) {
            $this->userRepository->syncProfiles($user, $data['profiles']);
        }

        $this->invalidateUserCache((int) $authUser->tenant_id);

        return $user->load(['profiles', 'tenant', 'jobPosition']);
    }

    public function deleteUserForCurrentTenant(User $user): bool
    {
        $authUser = Auth::user();

        if (
            !$authUser || !$authUser->tenant_id ||
            $user->tenant_id !== $authUser->tenant_id
        ) {
            return false;
        }

        if ($user->id === $authUser->id) {
            return false;
        }

        $deleted = $this->userRepository->deleteUser($user->id);

        if ($deleted) {
            $this->invalidateUserCache((int) $authUser->tenant_id);
        }

        return $deleted;
    }

    public function assignProfileForCurrentTenant(User $user, int $profileId): ?User
    {
        $authUser = Auth::user();

        if (
            !$authUser || !$authUser->tenant_id ||
            $user->tenant_id !== $authUser->tenant_id
        ) {
            return null;
        }

        $profile = $this->userRepository->findProfileByIdAndTenant($profileId, $authUser->tenant_id);

        if (!$profile) {
            return null;
        }

        $this->userRepository->attachProfile($user, $profileId);

        return $user->load(['profiles', 'tenant', 'jobPosition']);
    }

    public function removeProfileForCurrentTenant(User $user, int $profileId): ?User
    {
        $authUser = Auth::user();

        if (
            !$authUser || !$authUser->tenant_id ||
            $user->tenant_id !== $authUser->tenant_id
        ) {
            return null;
        }

        $this->userRepository->detachProfile($user, $profileId);

        return $user->load(['profiles', 'tenant', 'jobPosition']);
    }

    public function changePasswordForCurrentTenant(User $user, string $password): ?User
    {
        $authUser = Auth::user();

        if (
            !$authUser || !$authUser->tenant_id ||
            $user->tenant_id !== $authUser->tenant_id
        ) {
            return null;
        }

        return $this->userRepository->updateUser($user->id, ['password' => Hash::make($password)]);
    }

    public function getUserPermissionsForCurrentTenant(User $user)
    {
        $authUser = Auth::user();

        if (
            !$authUser || !$authUser->tenant_id ||
            $user->tenant_id !== $authUser->tenant_id
        ) {
            return null;
        }

        return $this->userRepository->getUserPermissions($user);
    }
}
