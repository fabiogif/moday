<?php

namespace App\Services\Seasonal;

use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\SeasonalAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeasonalTrendService
{
    private const MONTHS_PT = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',    4 => 'Abril',
        5 => 'Maio',    6 => 'Junho',     7 => 'Julho',    8 => 'Agosto',
        9 => 'Setembro',10 => 'Outubro',  11 => 'Novembro',12 => 'Dezembro',
    ];

    /**
     * Returns monthly revenue totals for the last N years (aggregated).
     * Useful for building a seasonality heatmap.
     */
    public function monthlyRevenueTrend(int $tenantId, int $years = 2): Collection
    {
        $since = Carbon::now()->subYears($years)->startOfMonth();

        $rows = DB::table('sale_orders')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['faturado', 'em_transito', 'entregue'])
            ->where('billed_at', '>=', $since)
            ->whereNotNull('billed_at')
            ->selectRaw('YEAR(billed_at) as yr, MONTH(billed_at) as mo, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('yr', 'mo')
            ->orderBy('yr')
            ->orderBy('mo')
            ->get();

        return $rows->map(fn ($r) => [
            'year'         => (int) $r->yr,
            'month'        => (int) $r->mo,
            'month_name'   => self::MONTHS_PT[(int) $r->mo],
            'revenue'      => round((float) $r->revenue, 2),
            'orders'       => (int) $r->orders,
        ]);
    }

    /**
     * Returns the average revenue per month across all years.
     * Useful to identify seasonal peaks (months consistently above average).
     */
    public function seasonalityIndex(int $tenantId, int $years = 2): Collection
    {
        $monthly = $this->monthlyRevenueTrend($tenantId, $years);

        // Average revenue per calendar month across years
        $byMonth = $monthly->groupBy('month')->map(function (Collection $group, int $month) {
            $avg = $group->avg('revenue');
            return [
                'month'       => $month,
                'month_name'  => self::MONTHS_PT[$month],
                'avg_revenue' => round($avg, 2),
                'data_points' => $group->count(),
                'years'       => $group->pluck('year')->all(),
            ];
        })->values();

        $globalAvg = $byMonth->avg('avg_revenue');

        return $byMonth->map(fn ($m) => array_merge($m, [
            'index'    => $globalAvg > 0 ? round($m['avg_revenue'] / $globalAvg, 3) : 1.0,
            'is_peak'  => $m['avg_revenue'] > $globalAvg * 1.2,  // 20% above average = peak
            'is_low'   => $m['avg_revenue'] < $globalAvg * 0.8,  // 20% below average = low season
        ]));
    }

    /**
     * Returns top-selling products for the same month in previous years
     * — useful to anticipate demand for the current/upcoming month.
     */
    public function productDemandByMonth(int $tenantId, int $month, int $years = 2): Collection
    {
        $since = Carbon::now()->subYears($years)->startOfMonth();

        return DB::table('sale_order_items')
            ->join('sale_orders', 'sale_orders.id', '=', 'sale_order_items.sale_order_id')
            ->join('products', 'products.id', '=', 'sale_order_items.product_id')
            ->where('sale_orders.tenant_id', $tenantId)
            ->whereIn('sale_orders.status', ['faturado', 'entregue'])
            ->whereNotNull('sale_orders.billed_at')
            ->where('sale_orders.billed_at', '>=', $since)
            ->whereRaw('MONTH(sale_orders.billed_at) = ?', [$month])
            ->whereNotNull('sale_order_items.product_id')
            ->selectRaw('
                sale_order_items.product_id,
                products.name as product_name,
                products.sku,
                SUM(sale_order_items.quantity) as total_qty,
                SUM(sale_order_items.subtotal) as total_revenue,
                COUNT(DISTINCT sale_orders.id) as order_count
            ')
            ->groupBy('sale_order_items.product_id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'product_id'    => (int) $r->product_id,
                'product_name'  => $r->product_name,
                'sku'           => $r->sku,
                'total_qty'     => round((float) $r->total_qty, 2),
                'total_revenue' => round((float) $r->total_revenue, 2),
                'order_count'   => (int) $r->order_count,
            ]);
    }

    /**
     * Generates seasonal peak alerts for upcoming months with historically high demand.
     */
    public function generatePeakAlerts(int $tenantId): int
    {
        $created  = 0;
        $index    = $this->seasonalityIndex($tenantId);
        $today    = Carbon::today();
        $upcoming = [
            $today->copy()->addMonth()->month,
            $today->copy()->addMonths(2)->month,
        ];

        foreach ($upcoming as $month) {
            $monthData = $index->firstWhere('month', $month);
            if (!$monthData || !$monthData['is_peak']) continue;

            $alreadyAlerted = SeasonalAlert::forTenant($tenantId)
                ->where('type', 'seasonal_peak')
                ->whereJsonContains('metadata->month', $month)
                ->where('created_at', '>=', $today->copy()->startOfMonth())
                ->exists();

            if ($alreadyAlerted) continue;

            SeasonalAlert::create([
                'tenant_id' => $tenantId,
                'type'      => 'seasonal_peak',
                'priority'  => 'normal',
                'title'     => "Pico sazonal previsto: {$monthData['month_name']}",
                'body'      => "{$monthData['month_name']} é historicamente {$monthData['index']}x acima da média. Reforce o estoque dos produtos mais vendidos neste período.",
                'metadata'  => [
                    'month'       => $month,
                    'month_name'  => $monthData['month_name'],
                    'index'       => $monthData['index'],
                    'avg_revenue' => $monthData['avg_revenue'],
                ],
                'reference_date' => Carbon::createFromDate($today->year, $month, 1)->toDateString(),
            ]);

            $created++;
        }

        return $created;
    }
}
