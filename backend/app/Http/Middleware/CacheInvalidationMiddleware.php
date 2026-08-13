<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CacheService;
use Symfony\Component\HttpFoundation\Response;

class CacheInvalidationMiddleware
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only invalidate cache for successful requests
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->invalidateCacheForRequest($request);
        }

        return $response;
    }

    /**
     * Invalidate cache based on the request
     */
    private function invalidateCacheForRequest(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->tenant_id) {
            return;
        }

        $tenantId = $user->tenant_id;
        $method = $request->method();
        $path = $request->path();

        // Only invalidate for write operations
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        // Invalidate cache based on the endpoint
        if (str_contains($path, 'client')) {
            $this->cacheService->invalidateClientCache($tenantId);
        } elseif (str_contains($path, 'product')) {
            $this->cacheService->invalidateProductCache($tenantId);
        } elseif (str_contains($path, 'order')) {
            $this->cacheService->invalidateOrderCache($tenantId);
        } elseif (str_contains($path, 'category')) {
            $this->cacheService->invalidateCategoryCache($tenantId);
        } elseif (str_contains($path, 'table')) {
            $this->cacheService->invalidateTableCache($tenantId);
        } elseif (str_contains($path, 'user')) {
            Cache::forget("user_list_{$tenantId}");
            Cache::forget("dashboard_data_{$tenantId}");
        } elseif (str_contains($path, 'profile')) {
            Cache::forget("profile_list_{$tenantId}");
            Cache::forget("dashboard_data_{$tenantId}");
        } elseif (str_contains($path, 'permission')) {
            Cache::forget("permission_list_{$tenantId}");
        } elseif (str_contains($path, 'role')) {
            Cache::forget("role_list_{$tenantId}");
        } else {
            // For other endpoints, invalidate all cache
            $this->cacheService->invalidateAllTenantCache($tenantId);
        }
    }
}
