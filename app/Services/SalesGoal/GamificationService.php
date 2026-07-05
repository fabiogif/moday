<?php

namespace App\Services\SalesGoal;

use App\Models\GamificationPointLog;
use App\Models\SaleOrder;
use App\Models\SalesGoal;
use App\Repositories\Contracts\GamificationProfileRepositoryInterface;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GamificationService
{
    private const POINTS_PER_100_REAIS = 1;
    private const GOAL_COMPLETION_BONUS = 50;

    public function __construct(
        private readonly GamificationProfileRepositoryInterface $profileRepository,
        private readonly CacheService $cacheService
    ) {}

    public function getProfile(int $tenantId, int $userId): array
    {
        return $this->cacheService->getGamificationProfile($tenantId, $userId, function () use ($tenantId, $userId) {
            $profile = $this->profileRepository->findOrCreateForUser($tenantId, $userId);

            $achievements = DB::table('user_achievements')
                ->join('achievement_definitions', 'user_achievements.achievement_definition_id', '=', 'achievement_definitions.id')
                ->where('user_achievements.user_id', $userId)
                ->select(
                    'achievement_definitions.key',
                    'achievement_definitions.name',
                    'achievement_definitions.description',
                    'achievement_definitions.icon',
                    'achievement_definitions.badge_color',
                    'achievement_definitions.category',
                    'user_achievements.unlocked_at'
                )
                ->orderByDesc('user_achievements.unlocked_at')
                ->get();

            return [
                'total_points' => $profile->total_points,
                'current_streak_days' => $profile->current_streak_days,
                'best_streak_days' => $profile->best_streak_days,
                'last_activity_date' => $profile->last_activity_date?->toDateString(),
                'achievements_count' => $profile->achievements_count,
                'achievements' => $achievements,
            ];
        });
    }

    public function awardPointsForSale(int $tenantId, int $userId, SaleOrder $order): void
    {
        $orderTotal = (float) $order->total;
        $points = (int) floor($orderTotal / 100) * self::POINTS_PER_100_REAIS;

        if ($points <= 0) {
            return;
        }

        DB::transaction(function () use ($tenantId, $userId, $points, $order) {
            $profile = $this->profileRepository->findOrCreateForUser($tenantId, $userId);
            $newTotal = $profile->total_points + $points;

            $profile->update(['total_points' => $newTotal]);

            GamificationPointLog::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'points' => $points,
                'balance_after' => $newTotal,
                'source_type' => 'sale_completed',
                'source_id' => $order->id,
                'description' => "Venda #" . ($order->order_number ?? $order->id) . " - R$ " . number_format($order->total, 2, ',', '.'),
            ]);
        });

        $this->cacheService->invalidateGamificationCache($tenantId);
    }

    public function awardBonusForGoal(int $tenantId, int $userId, SalesGoal $goal): void
    {
        DB::transaction(function () use ($tenantId, $userId, $goal) {
            $profile = $this->profileRepository->findOrCreateForUser($tenantId, $userId);
            $newTotal = $profile->total_points + self::GOAL_COMPLETION_BONUS;

            $profile->update(['total_points' => $newTotal]);

            GamificationPointLog::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'points' => self::GOAL_COMPLETION_BONUS,
                'balance_after' => $newTotal,
                'source_type' => 'goal_reached',
                'source_id' => $goal->id,
                'description' => "Meta concluída: {$goal->title}",
            ]);
        });

        $this->cacheService->invalidateGamificationCache($tenantId);

        Log::info('Bônus de meta concedido', ['user_id' => $userId, 'goal_id' => $goal->id]);
    }

    public function updateStreak(int $tenantId, int $userId): void
    {
        $profile = $this->profileRepository->findOrCreateForUser($tenantId, $userId);
        $today = Carbon::today();

        if ($profile->last_activity_date && $profile->last_activity_date->isSameDay($today)) {
            return;
        }

        $yesterday = $today->copy()->subDay();
        $isConsecutive = $profile->last_activity_date && $profile->last_activity_date->isSameDay($yesterday);

        $newStreak = $isConsecutive ? $profile->current_streak_days + 1 : 1;
        $bestStreak = max($profile->best_streak_days, $newStreak);

        $profile->update([
            'current_streak_days' => $newStreak,
            'best_streak_days' => $bestStreak,
            'last_activity_date' => $today,
        ]);
    }

    public function getPointsLeaderboard(int $tenantId, int $limit = 20): array
    {
        return $this->cacheService->getRankingList($tenantId, ['type' => 'gamification', 'limit' => $limit], function () use ($tenantId, $limit) {
            $profiles = $this->profileRepository->getLeaderboard($tenantId, $limit);

            $ranked = [];
            $rank = 1;

            foreach ($profiles as $profile) {
                $ranked[] = [
                    'rank' => $rank++,
                    'user_id' => $profile->user_id,
                    'user_name' => $profile->user->name ?? 'Não identificado',
                    'avatar' => $profile->user->avatar ?? null,
                    'total_points' => $profile->total_points,
                    'achievements_count' => $profile->achievements_count,
                    'current_streak' => $profile->current_streak_days,
                ];
            }

            return $ranked;
        });
    }

    public function getPointHistory(int $tenantId, int $userId, int $limit = 50): array
    {
        return GamificationPointLog::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'points' => $log->points,
                'balance_after' => $log->balance_after,
                'source_type' => $log->source_type,
                'description' => $log->description,
                'created_at' => $log->created_at?->toISOString(),
            ])
            ->toArray();
    }
}
