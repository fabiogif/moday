<?php

namespace App\Repositories;

use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Client;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fonte de dado: `SaleOrder` (pedido de venda B2B oficial), não `Order`
 * (quadro Kanban operacional de preparo — só reflete pedidos vindos da loja
 * pública, ver docblock de `PublicOrderService`). Pedidos criados no painel
 * web ou no app de campo só existem como `SaleOrder`, então usar `Order`
 * aqui deixava os indicadores cegos pra maior parte dos pedidos reais.
 */
class DashboardRepository implements DashboardRepositoryInterface
{
    /** Não conta como "pedido real" nos indicadores: orçamento (ainda rascunho) e cancelado. */
    private const EXCLUDED_STATUSES = ['orcamento', 'cancelado'];

    public function __construct(
        protected SaleOrder $saleOrderModel,
        protected Client $clientModel
    ) {}

    public function getTotalRevenue(int $tenantId, string $startDate, string $endDate): float
    {
        return $this->saleOrderModel
            ->where('tenant_id', $tenantId)
            ->whereNull('archived_at')
            ->whereBetween('ordered_at', [$startDate, $endDate])
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->sum('total');
    }

    public function getRevenueByPeriod(int $tenantId, string $startDate, string $groupBy = 'month'): array
    {
        // PostgreSQL format patterns
        $format = match ($groupBy) {
            'day' => 'YYYY-MM-DD',
            'week' => 'IYYY-IW',  // ISO week
            'month' => 'YYYY-MM',
            'year' => 'YYYY',
            default => 'YYYY-MM'
        };

        // Check database driver
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'pgsql') {
            // PostgreSQL syntax
            $selectRaw = "TO_CHAR(ordered_at, '{$format}') as period, SUM(total) as revenue, COUNT(*) as orders";
        } else {
            // MySQL syntax
            $mysqlFormat = match ($groupBy) {
                'day' => '%Y-%m-%d',
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                'year' => '%Y',
                default => '%Y-%m'
            };
            $selectRaw = "DATE_FORMAT(ordered_at, '{$mysqlFormat}') as period, SUM(total) as revenue, COUNT(*) as orders";
        }

        return $this->saleOrderModel
            ->where('tenant_id', $tenantId)
            ->whereNull('archived_at')
            ->where('ordered_at', '>=', $startDate)
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->selectRaw($selectRaw)
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
            ->whereHas('saleOrders', function ($query) use ($startDate) {
                $query->where('ordered_at', '>=', $startDate)
                    ->whereNotIn('status', self::EXCLUDED_STATUSES);
            })
            ->count();
    }

    public function getTotalOrders(int $tenantId, string $startDate, string $endDate): int
    {
        return $this->saleOrderModel
            ->where('tenant_id', $tenantId)
            ->whereNull('archived_at')
            ->whereBetween('ordered_at', [$startDate, $endDate])
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->count();
    }

    public function getConversionRate(int $tenantId, string $startDate): array
    {
        $totalVisits = $this->clientModel
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalOrders = $this->saleOrderModel
            ->where('tenant_id', $tenantId)
            ->whereNull('archived_at')
            ->where('ordered_at', '>=', $startDate)
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
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
        // Check database driver
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'pgsql') {
            // PostgreSQL syntax
            $selectRaw = "TO_CHAR(ordered_at, 'YYYY-MM') as month, SUM(total) as revenue, COUNT(*) as orders";
        } else {
            // MySQL syntax
            $selectRaw = 'DATE_FORMAT(ordered_at, "%Y-%m") as month, SUM(total) as revenue, COUNT(*) as orders';
        }

        return $this->saleOrderModel
            ->where('tenant_id', $tenantId)
            ->whereNull('archived_at')
            ->where('ordered_at', '>=', $startDate)
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->selectRaw($selectRaw)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getRecentTransactions(int $tenantId, int $limit = 10): array
    {
        return $this->saleOrderModel
            ->where('tenant_id', $tenantId)
            ->whereNull('archived_at')
            ->with(['client:id,name,company_name,trade_name'])
            ->orderBy('ordered_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getProfitMetrics(int $tenantId, string $startDate, string $endDate): array
    {
        $result = DB::table('sale_order_items')
            ->join('products', 'sale_order_items.product_id', '=', 'products.id')
            ->join('sale_orders', 'sale_order_items.sale_order_id', '=', 'sale_orders.id')
            ->where('sale_orders.tenant_id', $tenantId)
            ->whereNull('sale_orders.archived_at')
            ->whereBetween('sale_orders.ordered_at', [$startDate, $endDate])
            ->whereNotIn('sale_orders.status', self::EXCLUDED_STATUSES)
            ->selectRaw('
                SUM(sale_order_items.subtotal) as revenue,
                SUM(COALESCE(products.price_cost, 0) * sale_order_items.quantity) as cost
            ')
            ->first();

        $revenue = (float) ($result->revenue ?? 0);
        $cost    = (float) ($result->cost ?? 0);

        return [
            'revenue' => $revenue,
            'cost'    => $cost,
            'profit'  => $revenue - $cost,
            'margin'  => $revenue > 0 ? round((($revenue - $cost) / $revenue) * 100, 1) : 0,
        ];
    }

    public function getTopProducts(int $tenantId, string $startDate, int $limit = 10): array
    {
        return DB::table('sale_order_items')
            ->join('products', 'sale_order_items.product_id', '=', 'products.id')
            ->join('sale_orders', 'sale_order_items.sale_order_id', '=', 'sale_orders.id')
            ->where('sale_orders.tenant_id', $tenantId)
            ->whereNull('sale_orders.archived_at')
            ->where('sale_orders.ordered_at', '>=', $startDate)
            ->whereNotIn('sale_orders.status', self::EXCLUDED_STATUSES)
            ->select(
                'products.id',
                'products.uuid',
                'products.name',
                'products.image',
                'products.price',
                DB::raw('SUM(sale_order_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT sale_orders.id) as orders_count')
            )
            ->groupBy('products.id', 'products.uuid', 'products.name', 'products.image', 'products.price')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
