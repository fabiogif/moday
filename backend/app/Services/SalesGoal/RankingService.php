<?php

namespace App\Services\SalesGoal;

use App\Models\SaleOrder;
use App\Models\SalesGoal;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    public function getSellerRanking(int $tenantId, string $period = 'current_month', ?string $sortBy = 'revenue'): array
    {
        return $this->cacheService->getRankingList($tenantId, ['period' => $period, 'sort' => $sortBy, 'type' => 'seller'], function () use ($tenantId, $period, $sortBy) {
            [$start, $end] = $this->getPeriodDates($period);

            $sellers = SaleOrder::where('tenant_id', $tenantId)
                ->whereIn('status', ['aprovado', 'faturado', 'em_transito', 'entregue'])
                ->whereBetween('ordered_at', [$start, $end])
                ->whereNotNull('approved_by')
                ->select('approved_by')
                ->selectRaw('COUNT(*) as order_count')
                ->selectRaw('COALESCE(SUM(total), 0) as revenue')
                ->selectRaw('COALESCE(AVG(total), 0) as avg_ticket')
                ->groupBy('approved_by')
                ->orderByDesc($sortBy === 'orders' ? 'order_count' : 'revenue')
                ->get();

            $ranked = [];
            $rank = 1;

            foreach ($sellers as $seller) {
                $user = DB::table('users')->where('id', $seller->approved_by)->first(['id', 'name', 'avatar']);

                $goalCompletion = SalesGoal::where('tenant_id', $tenantId)
                    ->where('goal_type', 'seller')
                    ->where('target_user_id', $seller->approved_by)
                    ->where('status', 'active')
                    ->where('period_start', '<=', $end)
                    ->where('period_end', '>=', $start)
                    ->avg('completion_percent');

                $ranked[] = [
                    'rank' => $rank++,
                    'user_id' => $seller->approved_by,
                    'user_name' => $user->name ?? 'Não identificado',
                    'avatar' => $user->avatar ?? null,
                    'revenue' => round((float) $seller->revenue, 2),
                    'order_count' => (int) $seller->order_count,
                    'avg_ticket' => round((float) $seller->avg_ticket, 2),
                    'goal_completion' => round((float) ($goalCompletion ?? 0), 2),
                ];
            }

            return $ranked;
        });
    }

    public function getTeamRanking(int $tenantId, string $period = 'current_month'): array
    {
        return $this->cacheService->getRankingList($tenantId, ['period' => $period, 'type' => 'team'], function () use ($tenantId, $period) {
            [$start, $end] = $this->getPeriodDates($period);

            $teams = DB::table('sale_orders')
                ->join('user_profiles', 'sale_orders.approved_by', '=', 'user_profiles.user_id')
                ->join('profiles', 'user_profiles.profile_id', '=', 'profiles.id')
                ->where('sale_orders.tenant_id', $tenantId)
                ->whereIn('sale_orders.status', ['aprovado', 'faturado', 'em_transito', 'entregue'])
                ->whereBetween('sale_orders.ordered_at', [$start, $end])
                ->whereNotNull('sale_orders.approved_by')
                ->select('profiles.id as profile_id', 'profiles.name as profile_name')
                ->selectRaw('COUNT(DISTINCT sale_orders.id) as order_count')
                ->selectRaw('COALESCE(SUM(sale_orders.total), 0) as revenue')
                ->selectRaw('COUNT(DISTINCT sale_orders.approved_by) as member_count')
                ->groupBy('profiles.id', 'profiles.name')
                ->orderByDesc('revenue')
                ->get();

            $ranked = [];
            $rank = 1;

            foreach ($teams as $team) {
                $ranked[] = [
                    'rank' => $rank++,
                    'profile_id' => $team->profile_id,
                    'profile_name' => $team->profile_name,
                    'revenue' => round((float) $team->revenue, 2),
                    'order_count' => (int) $team->order_count,
                    'member_count' => (int) $team->member_count,
                ];
            }

            return $ranked;
        });
    }

    public function getMyPosition(int $tenantId, int $userId, string $period = 'current_month'): array
    {
        $ranking = $this->getSellerRanking($tenantId, $period);
        $total = count($ranking);
        $myEntry = collect($ranking)->firstWhere('user_id', $userId);

        return [
            'position' => $myEntry['rank'] ?? null,
            'total_participants' => $total,
            'revenue' => $myEntry['revenue'] ?? 0,
            'order_count' => $myEntry['order_count'] ?? 0,
            'goal_completion' => $myEntry['goal_completion'] ?? 0,
        ];
    }

    private function getPeriodDates(string $period): array
    {
        return match ($period) {
            'current_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'current_quarter' => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()],
            'current_year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }
}
