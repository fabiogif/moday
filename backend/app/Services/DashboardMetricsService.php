<?php

namespace App\Services;

use App\Repositories\contracts\DashboardRepositoryInterface;
use Carbon\Carbon;

readonly class DashboardMetricsService
{
    public function __construct(
        private DashboardRepositoryInterface $dashboardRepository,
        private CacheService $cacheService
    ) {}

    /**
     * Get enhanced metrics overview
     */
    public function getMetricsOverview(int $tenantId): array
    {
        return $this->cacheService->getDashboardMetrics($tenantId, function () use ($tenantId) {
            $currentMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();

            // Total Revenue
            $currentRevenue = $this->dashboardRepository->getTotalRevenue(
                $tenantId,
                $currentMonth->toDateTimeString(),
                Carbon::now()->toDateTimeString()
            );

            $lastMonthRevenue = $this->dashboardRepository->getTotalRevenue(
                $tenantId,
                $lastMonth->toDateTimeString(),
                $currentMonth->toDateTimeString()
            );

            $revenueGrowth = $this->calculateGrowth($currentRevenue, $lastMonthRevenue);

            // Last 6 months revenue
            $last6MonthsRevenue = $this->formatRevenueByPeriod(
                $this->dashboardRepository->getRevenueByPeriod(
                    $tenantId,
                    $sixMonthsAgo->toDateTimeString(),
                    'month'
                )
            );

            // Active Clients
            $activeClients = $this->dashboardRepository->getActiveClients(
                $tenantId,
                $currentMonth->toDateTimeString()
            );

            $lastMonthActiveClients = $this->dashboardRepository->getActiveClients(
                $tenantId,
                $lastMonth->toDateTimeString()
            );

            $clientRetention = $this->calculateRetention($activeClients, $lastMonthActiveClients);

            // Total Orders
            $totalOrders = $this->dashboardRepository->getTotalOrders(
                $tenantId,
                $currentMonth->toDateTimeString(),
                Carbon::now()->toDateTimeString()
            );

            $lastMonthOrders = $this->dashboardRepository->getTotalOrders(
                $tenantId,
                $lastMonth->toDateTimeString(),
                $currentMonth->toDateTimeString()
            );

            $ordersGrowth = $this->calculateGrowth($totalOrders, $lastMonthOrders);

            // Conversion Rate
            $currentConversion = $this->dashboardRepository->getConversionRate(
                $tenantId,
                $currentMonth->toDateTimeString()
            );

            $lastMonthConversion = $this->dashboardRepository->getConversionRate(
                $tenantId,
                $lastMonth->toDateTimeString()
            );

            $conversionGrowth = $this->calculateGrowth(
                $currentConversion['conversion_rate'],
                $lastMonthConversion['conversion_rate']
            );

            return [
                'total_revenue' => $this->buildRevenueMetric(
                    $currentRevenue,
                    $revenueGrowth,
                    $last6MonthsRevenue
                ),
                'active_clients' => $this->buildClientMetric(
                    $activeClients,
                    $clientRetention
                ),
                'total_orders' => $this->buildOrderMetric(
                    $totalOrders,
                    $ordersGrowth
                ),
                'conversion_rate' => $this->buildConversionMetric(
                    $currentConversion['conversion_rate'],
                    $conversionGrowth
                )
            ];
        });
    }

    /**
     * Get sales performance data
     */
    public function getSalesPerformance(int $tenantId): array
    {
        return $this->cacheService->getSalesPerformance($tenantId, function () use ($tenantId) {
            $last12Months = Carbon::now()->subMonths(12)->startOfMonth();

            $performanceData = $this->dashboardRepository->getSalesPerformance(
                $tenantId,
                $last12Months->toDateTimeString()
            );

            $monthlySales = $this->formatSalesPerformance($performanceData);

            $currentMonthData = end($monthlySales) ?: null;

            return [
                'monthly_data' => $monthlySales,
                'current_month' => $currentMonthData,
                'summary' => $this->calculateSalesSummary($monthlySales)
            ];
        });
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions(int $tenantId, int $limit = 10): array
    {
        return $this->cacheService->getRecentTransactions($tenantId, function () use ($tenantId, $limit) {
            $transactions = $this->dashboardRepository->getRecentTransactions($tenantId, $limit);

            return [
                'transactions' => $this->formatTransactions($transactions),
                'total' => count($transactions)
            ];
        });
    }

    /**
     * Get top products
     */
    public function getTopProducts(int $tenantId, int $limit = 10): array
    {
        return $this->cacheService->getTopProducts($tenantId, function () use ($tenantId, $limit) {
            $currentMonth = Carbon::now()->startOfMonth();

            $products = $this->dashboardRepository->getTopProducts(
                $tenantId,
                $currentMonth->toDateTimeString(),
                $limit
            );

            $formattedProducts = $this->formatTopProducts($products);
            $totalRevenue = array_sum(array_column($formattedProducts, 'total_revenue'));

            return [
                'products' => $formattedProducts,
                'total_products' => count($formattedProducts),
                'total_revenue' => $totalRevenue,
                'formatted_total_revenue' => 'R$ ' . number_format($totalRevenue, 2, ',', '.')
            ];
        });
    }

    /**
     * Calculate growth percentage
     */
    private function calculateGrowth(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Calculate retention percentage
     */
    private function calculateRetention(int $current, int $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round(($current / $previous) * 100, 1);
    }

    /**
     * Build revenue metric array
     */
    private function buildRevenueMetric(float $revenue, float $growth, array $chartData): array
    {
        return [
            'value' => $revenue,
            'formatted' => 'R$ ' . number_format($revenue, 2, ',', '.'),
            'growth' => $growth,
            'trend' => $growth >= 0 ? 'up' : 'down',
            'subtitle' => 'Tendência em alta neste mês',
            'description' => 'Receita dos últimos 6 meses',
            'chart_data' => $chartData
        ];
    }

    /**
     * Build client metric array
     */
    private function buildClientMetric(int $clients, float $retention): array
    {
        return [
            'value' => $clients,
            'growth' => $retention,
            'trend' => $retention >= 100 ? 'up' : 'down',
            'subtitle' => 'Forte retenção de usuários',
            'description' => 'O engajamento excede as metas'
        ];
    }

    /**
     * Build order metric array
     */
    private function buildOrderMetric(int $orders, float $growth): array
    {
        $subtitle = $growth < 0 
            ? 'Queda de ' . abs($growth) . '% neste período' 
            : 'Crescimento de ' . $growth . '% neste período';

        $description = $growth < 0 
            ? 'O volume de pedidos precisa de atenção' 
            : 'Volume de pedidos em crescimento';

        return [
            'value' => $orders,
            'growth' => $growth,
            'trend' => $growth >= 0 ? 'up' : 'down',
            'subtitle' => $subtitle,
            'description' => $description
        ];
    }

    /**
     * Build conversion metric array
     */
    private function buildConversionMetric(float $rate, float $growth): array
    {
        return [
            'value' => round($rate, 1),
            'formatted' => round($rate, 1) . '%',
            'growth' => $growth,
            'trend' => $growth >= 0 ? 'up' : 'down',
            'subtitle' => 'Aumento constante do desempenho',
            'description' => 'Atende às projeções de conversão'
        ];
    }

    /**
     * Format revenue by period
     */
    private function formatRevenueByPeriod(array $data): array
    {
        return array_map(function ($item) {
            return [
                'month' => Carbon::parse($item['period'] . '-01')->format('M/Y'),
                'revenue' => (float) $item['revenue']
            ];
        }, $data);
    }

    /**
     * Format sales performance data
     */
    private function formatSalesPerformance(array $data): array
    {
        return array_map(function ($item) {
            $revenue = (float) $item['revenue'];
            $goal = $revenue * 1.2; // 20% above current

            return [
                'month' => Carbon::parse($item['month'] . '-01')->format('M/Y'),
                'sales' => $revenue,
                'goal' => $goal,
                'orders' => (int) $item['orders'],
                'performance' => $goal > 0 ? round(($revenue / $goal) * 100, 1) : 0
            ];
        }, $data);
    }

    /**
     * Calculate sales summary
     */
    private function calculateSalesSummary(array $monthlySales): array
    {
        $totalSales = array_sum(array_column($monthlySales, 'sales'));
        $totalGoal = array_sum(array_column($monthlySales, 'goal'));
        $performances = array_column($monthlySales, 'performance');
        $avgPerformance = count($performances) > 0 
            ? array_sum($performances) / count($performances) 
            : 0;

        return [
            'total_sales' => $totalSales,
            'total_goal' => $totalGoal,
            'avg_performance' => round($avgPerformance, 1)
        ];
    }

    /**
     * Format transactions
     */
    private function formatTransactions(array $transactions): array
    {
        return array_map(function ($transaction) {
            $createdAt = Carbon::parse($transaction['created_at']);
            
            return [
                'id' => $transaction['id'],
                'identify' => $transaction['identify'],
                'client' => [
                    'name' => $transaction['client']['name'] ?? 'Cliente não identificado',
                    'email' => $transaction['client']['email'] ?? null
                ],
                'table' => $transaction['table']['name'] ?? null,
                'total' => (float) $transaction['total'],
                'formatted_total' => 'R$ ' . number_format($transaction['total'], 2, ',', '.'),
                'status' => $transaction['status'],
                'payment_method' => $transaction['payment_method'] ?? null,
                'created_at' => $createdAt->format('d/m/Y H:i'),
                'created_at_human' => $createdAt->diffForHumans()
            ];
        }, $transactions);
    }

    /**
     * Format top products
     */
    private function formatTopProducts(array $products): array
    {
        return array_map(function ($product, $index) {
            // Convert stdClass to array if needed
            $productArray = is_object($product) ? (array) $product : $product;
            
            return [
                'rank' => $index + 1,
                'id' => $productArray['id'],
                'uuid' => $productArray['uuid'],
                'name' => $productArray['name'],
                'image' => $productArray['image'] ?? null,
                'price' => (float) $productArray['price'],
                'formatted_price' => 'R$ ' . number_format($productArray['price'], 2, ',', '.'),
                'total_quantity' => (int) $productArray['total_quantity'],
                'total_revenue' => (float) $productArray['total_revenue'],
                'formatted_revenue' => 'R$ ' . number_format($productArray['total_revenue'], 2, ',', '.'),
                'orders_count' => (int) $productArray['orders_count']
            ];
        }, $products, array_keys($products));
    }
}
