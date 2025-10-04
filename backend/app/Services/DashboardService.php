<?php

namespace App\Services;

use App\Repositories\contracts\ClientRepositoryInterface;
use App\Repositories\contracts\ProductRepositoryInterface;
use App\Repositories\contracts\OrderRepositoryInterface;
use App\Repositories\contracts\CategoryRepositoryInterface;
use App\Repositories\contracts\TableRepositoryInterface;

class DashboardService
{
    public function __construct(
        protected ClientRepositoryInterface $clientRepository,
        protected ProductRepositoryInterface $productRepository,
        protected OrderRepositoryInterface $orderRepository,
        protected CategoryRepositoryInterface $categoryRepository,
        protected TableRepositoryInterface $tableRepository,
        protected CacheService $cacheService
    )
    {}

    /**
     * Get comprehensive dashboard data with cache
     */
    public function getDashboardData(int $tenantId): array
    {
        return $this->cacheService->getDashboardData($tenantId, function () use ($tenantId) {
            return $this->calculateDashboardData($tenantId);
        });
    }

    /**
     * Calculate dashboard data (without cache)
     */
    private function calculateDashboardData(int $tenantId): array
    {
        $currentMonthStart = \Carbon\Carbon::now()->startOfMonth();
        $currentMonthEnd = \Carbon\Carbon::now()->endOfMonth();
        $previousMonthStart = \Carbon\Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = \Carbon\Carbon::now()->subMonth()->endOfMonth();

        // Get basic counts
        $totalClients = $this->clientRepository->getClientsByTenant($tenantId)->count();
        $totalProducts = $this->productRepository->getProductsByTenantUuid($tenantId, [])->count();
        $totalOrders = \App\Models\Order::where('tenant_id', $tenantId)->count();
        $totalCategories = \App\Models\Category::where('tenant_id', $tenantId)->count();
        $totalTables = \App\Models\Table::where('tenant_id', $tenantId)->count();

        // Get current month data
        $currentMonthOrders = \App\Models\Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->get();

        $currentMonthRevenue = $currentMonthOrders->sum('total');
        $currentMonthOrdersCount = $currentMonthOrders->count();

        // Get previous month data
        $previousMonthOrders = \App\Models\Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->get();

        $previousMonthRevenue = $previousMonthOrders->sum('total');
        $previousMonthOrdersCount = $previousMonthOrders->count();

        // Calculate growth percentages
        $revenueGrowth = $previousMonthRevenue > 0 
            ? round((($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 1)
            : ($currentMonthRevenue > 0 ? 100 : 0);

        $ordersGrowth = $previousMonthOrdersCount > 0 
            ? round((($currentMonthOrdersCount - $previousMonthOrdersCount) / $previousMonthOrdersCount) * 100, 1)
            : ($currentMonthOrdersCount > 0 ? 100 : 0);

        // Get orders by status
        $ordersByStatus = $currentMonthOrders->groupBy('status')
            ->map(function ($orders) {
                return $orders->count();
            })
            ->toArray();

        // Get top products (by order frequency)
        $topProducts = $this->getTopProducts($tenantId, $currentMonthStart, $currentMonthEnd);

        // Get recent orders
        $recentOrders = \App\Models\Order::where('tenant_id', $tenantId)
            ->with(['client', 'products'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'overview' => [
                'total_clients' => $totalClients,
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'total_categories' => $totalCategories,
                'total_tables' => $totalTables,
            ],
            'revenue' => [
                'current_month' => round($currentMonthRevenue, 2),
                'previous_month' => round($previousMonthRevenue, 2),
                'growth' => $revenueGrowth
            ],
            'orders' => [
                'current_month' => $currentMonthOrdersCount,
                'previous_month' => $previousMonthOrdersCount,
                'growth' => $ordersGrowth,
                'by_status' => $ordersByStatus
            ],
            'top_products' => $topProducts,
            'recent_orders' => $recentOrders,
            'period' => [
                'current_month' => $currentMonthStart->format('Y-m'),
                'previous_month' => $previousMonthStart->format('Y-m')
            ]
        ];
    }

    /**
     * Get top products by order frequency
     */
    private function getTopProducts(int $tenantId, $startDate, $endDate): array
    {
        $orders = \App\Models\Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('products')
            ->get();

        $productCounts = [];
        
        foreach ($orders as $order) {
            foreach ($order->products as $product) {
                $productId = $product->id;
                $productName = $product->name;
                
                if (!isset($productCounts[$productId])) {
                    $productCounts[$productId] = [
                        'id' => $productId,
                        'name' => $productName,
                        'count' => 0,
                        'revenue' => 0
                    ];
                }
                
                $productCounts[$productId]['count'] += $product->pivot->qty;
                $productCounts[$productId]['revenue'] += $product->pivot->qty * $product->pivot->price;
            }
        }

        // Sort by count and return top 5
        usort($productCounts, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_slice($productCounts, 0, 5);
    }

    /**
     * Invalidate dashboard cache
     */
    public function invalidateDashboardCache(int $tenantId): void
    {
        $this->cacheService->invalidateAllTenantCache($tenantId);
    }
}
