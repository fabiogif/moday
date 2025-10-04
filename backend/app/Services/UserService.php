<?php

namespace App\Services;

use App\Models\User;
use App\Services\ListingCacheService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class UserService
{
    protected $listingCacheService;

    public function __construct(ListingCacheService $listingCacheService)
    {
        $this->listingCacheService = $listingCacheService;
    }

    /**
     * Get paginated users with cache
     */
    public function getUsersByTenant(Request $request, int $tenantId): LengthAwarePaginator
    {
        $perPage = min($request->get('per_page', 15), 100);
        $filters = $request->only(['name', 'email', 'status']);
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Create cache key with all parameters
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
            function () use ($request, $tenantId, $perPage, $filters, $search, $sortBy, $sortDirection) {
                $query = User::with(['profiles', 'tenant'])
                    ->where('tenant_id', $tenantId);

                // Apply search
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                          ->orWhere('email', 'like', '%' . $search . '%');
                    });
                }

                // Apply filters
                if (isset($filters['name']) && $filters['name']) {
                    $query->where('name', 'like', '%' . $filters['name'] . '%');
                }
                
                if (isset($filters['email']) && $filters['email']) {
                    $query->where('email', 'like', '%' . $filters['email'] . '%');
                }
                
                if (isset($filters['status']) && $filters['status']) {
                    $query->where('status', $filters['status']);
                }

                // Apply sorting
                $query->orderBy($sortBy, $sortDirection);

                return $query->paginate($perPage);
            }
        );
    }

    /**
     * Invalidate user cache
     */
    public function invalidateUserCache(int $tenantId): void
    {
        $this->listingCacheService->invalidateUserListCache($tenantId);
    }

    /**
     * Get user statistics for a tenant
     */
    public function getUserStats(int $tenantId): array
    {
        $cacheKey = "user_stats_tenant_{$tenantId}";
        
        return Cache::remember($cacheKey, 15, function () use ($tenantId) {
            $totalUsers = User::where('tenant_id', $tenantId)->count();
            $activeUsers = User::where('tenant_id', $tenantId)->where('status', 'active')->count();
            $pendingUsers = User::where('tenant_id', $tenantId)->where('status', 'pending')->count();
            $inactiveUsers = User::where('tenant_id', $tenantId)->where('status', 'inactive')->count();
            
            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'pending_users' => $pendingUsers,
                'inactive_users' => $inactiveUsers,
                'last_updated' => now()->toISOString()
            ];
        });
    }
}
