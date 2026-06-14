<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\AccountReceivable;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExecutiveDashboardController extends Controller
{
    public function __construct(private readonly AuthTenantService $authTenantService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $start = Carbon::parse($request->get('start', now()->startOfMonth()));
            $end   = Carbon::parse($request->get('end', now()->endOfMonth()));

            $orders = SaleOrder::where('tenant_id', $tenantId)
                ->whereIn('status', ['faturado', 'entregue'])
                ->whereBetween('created_at', [$start, $end])
                ->with(['items.product:id,name,price_cost', 'approvedBy:id,name'])
                ->get();

            return ApiResponseClass::sendResponse([
                'period'              => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
                'revenue'             => $this->revenueMetrics($orders),
                'margin'              => $this->marginMetrics($orders),
                'sales_by_user'       => $this->salesByUser($orders),
                'returns'             => $this->returnMetrics($tenantId, $start, $end),
                'delinquency'         => $this->delinquencyMetrics($tenantId),
            ], 'Dashboard executivo gerado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar dashboard executivo');
        }
    }

    private function revenueMetrics($orders): array
    {
        return [
            'total'       => round($orders->sum('total'), 2),
            'avg_ticket'  => $orders->count() > 0 ? round($orders->avg('total'), 2) : 0,
            'order_count' => $orders->count(),
        ];
    }

    private function marginMetrics($orders): array
    {
        $totalRevenue = 0;
        $totalCost    = 0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $totalRevenue += $item->subtotal;
                $cost = ($item->product?->price_cost ?? 0) * $item->quantity;
                $totalCost += $cost;
            }
        }

        $grossMargin = $totalRevenue > 0
            ? round((($totalRevenue - $totalCost) / $totalRevenue) * 100, 2)
            : 0;

        return [
            'total_revenue'  => round($totalRevenue, 2),
            'total_cost'     => round($totalCost, 2),
            'gross_profit'   => round($totalRevenue - $totalCost, 2),
            'gross_margin_percent' => $grossMargin,
        ];
    }

    private function salesByUser($orders): array
    {
        return $orders->groupBy('user_id')
            ->map(function ($group) {
                $seller = $group->first()?->approvedBy;
                return [
                    'user_id'     => $group->first()?->user_id,
                    'user_name'   => $seller?->name ?? 'Não identificado',
                    'order_count' => $group->count(),
                    'revenue'     => round($group->sum('total'), 2),
                    'avg_ticket'  => round($group->avg('total'), 2),
                ];
            })
            ->sortByDesc('revenue')
            ->values()
            ->toArray();
    }

    private function returnMetrics(int $tenantId, Carbon $start, Carbon $end): array
    {
        $returns = SaleOrder::where('tenant_id', $tenantId)
            ->where('type', 'devolucao')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as count, SUM(total) as total_value')
            ->first();

        return [
            'count'       => (int) ($returns->count ?? 0),
            'total_value' => round($returns->total_value ?? 0, 2),
        ];
    }

    private function delinquencyMetrics(int $tenantId): array
    {
        $today = Carbon::today();

        $overdue = AccountReceivable::where('tenant_id', $tenantId)
            ->where('status', 'vencido')
            ->where('due_date', '<', $today)
            ->selectRaw('COUNT(*) as count, SUM(amount - amount_received) as balance')
            ->first();

        $total = AccountReceivable::where('tenant_id', $tenantId)
            ->whereIn('status', ['pendente', 'parcial', 'vencido'])
            ->sum('amount');

        $overdueBalance = round($overdue->balance ?? 0, 2);
        $rate = $total > 0 ? round(($overdueBalance / $total) * 100, 2) : 0;

        return [
            'overdue_count'   => (int) ($overdue->count ?? 0),
            'overdue_balance' => $overdueBalance,
            'total_receivable' => round($total, 2),
            'delinquency_rate' => $rate,
        ];
    }
}
