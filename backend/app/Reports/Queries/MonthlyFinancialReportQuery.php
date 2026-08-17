<?php

namespace App\Reports\Queries;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonthlyFinancialReportQuery
{
    public function execute(array $filters): Collection
    {
        $month = $filters['month'] ?? now()->month;
        $year = $filters['year'] ?? now()->year;
        $tenantId = $filters['tenant_id'] ?? null;
        
        // Criar data inicial e final do mês
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        
        $dayFunction = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%d', created_at)"
            : "DAY(created_at)";

        $query = DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("$dayFunction as day"),
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(total) as gross_revenue'),
                DB::raw('AVG(total) as average_ticket'),
                DB::raw('GROUP_CONCAT(DISTINCT payment_method) as payment_methods')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query
            ->groupBy(DB::raw('DATE(created_at)'), DB::raw($dayFunction))
            ->orderBy('date', 'asc')
            ->get();
    }
    
    /**
     * Retorna o total do mês anterior para comparação
     */
    public function getPreviousMonthTotal(array $filters): float
    {
        $month = $filters['month'] ?? now()->month;
        $year = $filters['year'] ?? now()->year;
        $tenantId = $filters['tenant_id'] ?? null;
        
        // Mês anterior
        $previousDate = Carbon::create($year, $month, 1)->subMonth();
        $startDate = $previousDate->copy()->startOfMonth()->startOfDay();
        $endDate = $previousDate->copy()->endOfMonth()->endOfDay();
        
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->sum('total') ?? 0;
    }
}

