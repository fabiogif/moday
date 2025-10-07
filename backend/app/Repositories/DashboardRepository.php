<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Client;
use App\Repositories\contracts\DashboardRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function __construct(
        protected Order $orderModel,
        protected Client $clientModel
    ) {}

    public function getTotalRevenue(int $tenantId, string $startDate, string $endDate): float
    {
        return $this->orderModel
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'Cancelado')
            ->sum('total');
    }

    public function getRevenueByPeriod(int $tenantId, string $startDate, string $groupBy = 'month'): array
    {
        $format = match ($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m'
        };

        return $this->orderModel
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->where('status', '!=', 'Cancelado')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as period, SUM(total) as revenue, COUNT(*) as orders")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    public function getActiveClients(int $tenantId, string $startDate): int
    {
        return $this->clientModel
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereHas('orders', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->count();
    }

    public function getTotalOrders(int $tenantId, string $startDate, string $endDate): int
    {
        return $this->orderModel
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    public function getConversionRate(int $tenantId, string $startDate): array
    {
        $totalVisits = $this->clientModel
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalOrders = $this->orderModel
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $conversionRate = $totalVisits > 0 
            ? ($totalOrders / $totalVisits) * 100 
            : 0;

        return [
            'total_visits' => $totalVisits,
            'total_orders' => $totalOrders,
            'conversion_rate' => round($conversionRate, 2)
        ];
    }

    public function getSalesPerformance(int $tenantId, string $startDate): array
    {
        return $this->orderModel
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->where('status', '!=', 'Cancelado')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getRecentTransactions(int $tenantId, int $limit = 10): array
    {
        return $this->orderModel
            ->where('tenant_id', $tenantId)
            ->with(['client:id,name,email', 'table:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getTopProducts(int $tenantId, string $startDate, int $limit = 10): array
    {
        return DB::table('order_product')
            ->join('products', 'order_product.product_id', '=', 'products.id')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->where('products.tenant_id', $tenantId)
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', '!=', 'Cancelado')
            ->select(
                'products.id',
                'products.uuid',
                'products.name',
                'products.image',
                'products.price',
                DB::raw('SUM(order_product.qty) as total_quantity'),
                DB::raw('SUM(order_product.price * order_product.qty) as total_revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as orders_count')
            )
            ->groupBy('products.id', 'products.uuid', 'products.name', 'products.image', 'products.price')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
