<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardMetricsRequest;
use App\Http\Responses\Dashboard\DashboardMetricsResponse;
use App\Http\Responses\Dashboard\SalesPerformanceResponse;
use App\Http\Responses\Dashboard\RecentTransactionsResponse;
use App\Http\Responses\Dashboard\TopProductsResponse;
use App\Services\DashboardMetricsService;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;

class DashboardMetricsController extends Controller
{
    public function __construct(
        protected DashboardMetricsService $dashboardMetricsService,
        protected CacheService $cacheService
    ) {}

    /**
     * Get enhanced metrics overview
     */
    public function getMetricsOverview(DashboardMetricsRequest $request): DashboardMetricsResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $metrics = $this->dashboardMetricsService->getMetricsOverview($tenantId);
        
        return new DashboardMetricsResponse($metrics);
    }

    /**
     * Get sales performance data
     */
    public function getSalesPerformance(DashboardMetricsRequest $request): SalesPerformanceResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $performance = $this->dashboardMetricsService->getSalesPerformance($tenantId);
        
        return new SalesPerformanceResponse($performance);
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions(DashboardMetricsRequest $request): RecentTransactionsResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $limit = $request->input('limit', 10);
        
        $transactions = $this->dashboardMetricsService->getRecentTransactions($tenantId, $limit);
        
        return new RecentTransactionsResponse($transactions);
    }

    /**
     * Get top products
     */
    public function getTopProducts(DashboardMetricsRequest $request): TopProductsResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $limit = $request->input('limit', 10);
        
        $products = $this->dashboardMetricsService->getTopProducts($tenantId, $limit);
        
        return new TopProductsResponse($products);
    }

    /**
     * Get realtime updates status
     */
    public function getRealtimeUpdates(DashboardMetricsRequest $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        return response()->json([
            'success' => true,
            'data' => [
                'tenant_id' => $tenantId,
                'realtime_enabled' => true,
                'channel' => "dashboard.{$tenantId}"
            ],
            'message' => 'Status de atualizações em tempo real'
        ]);
    }

    /**
     * Clear dashboard cache for tenant
     */
    public function clearCache(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $this->cacheService->invalidateDashboardCache($tenantId);

        return response()->json([
            'success' => true,
            'data' => ['tenant_id' => $tenantId],
            'message' => 'Cache do dashboard limpo com sucesso'
        ]);
    }
}

