<?php

namespace App\Services\SalesGoal;

use App\Models\SaleOrder;
use App\Models\SalesGoal;
use App\Models\UserAchievement;
use App\Repositories\Contracts\AchievementDefinitionRepositoryInterface;
use App\Repositories\Contracts\GamificationProfileRepositoryInterface;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AchievementService
{
    public function __construct(
        private readonly AchievementDefinitionRepositoryInterface $achievementRepository,
        private readonly GamificationProfileRepositoryInterface $profileRepository,
        private readonly NotificationService $notificationService
    ) {}

    public function checkAndUnlockAchievements(int $tenantId, int $userId, ?SaleOrder $order = null): array
    {
        $definitions = $this->achievementRepository->getActiveForTenant($tenantId);
        $unlocked = [];

        foreach ($definitions as $definition) {
            $alreadyUnlocked = UserAchievement::where('user_id', $userId)
                ->where('achievement_definition_id', $definition->id)
                ->exists();

            if ($alreadyUnlocked) {
                continue;
            }

            if ($this->checkTrigger($tenantId, $userId, $definition)) {
                $achievement = $this->unlockAchievement($tenantId, $userId, $definition, $order);
                $unlocked[] = $achievement;
            }
        }

        return $unlocked;
    }

    public function listDefinitions(int $tenantId)
    {
        return $this->achievementRepository->findAllForTenant($tenantId);
    }

    public function findDefinition(int $tenantId, int $id)
    {
        return $this->achievementRepository->findForTenant($tenantId, $id);
    }

    public function createDefinition(array $data, int $tenantId)
    {
        $data['tenant_id'] = $tenantId;
        return $this->achievementRepository->create($data);
    }

    public function updateDefinition(int $tenantId, int $id, array $data)
    {
        $definition = $this->achievementRepository->findForTenant($tenantId, $id);
        if (!$definition || ($definition->tenant_id && $definition->tenant_id !== $tenantId)) {
            return null;
        }
        return $this->achievementRepository->update($id, $data);
    }

    public function deleteDefinition(int $tenantId, int $id)
    {
        $definition = $this->achievementRepository->findForTenant($tenantId, $id);
        if (!$definition || ($definition->tenant_id && $definition->tenant_id !== $tenantId)) {
            return false;
        }
        return $this->achievementRepository->delete($id);
    }

    public function getUserAchievements(int $tenantId, int $userId)
    {
        return UserAchievement::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->with('achievementDefinition')
            ->orderByDesc('unlocked_at')
            ->get();
    }

    private function checkTrigger(int $tenantId, int $userId, $definition): bool
    {
        $config = $definition->trigger_config;
        $threshold = $config['threshold'] ?? 0;
        $period = $config['period'] ?? null;

        [$start, $end] = $this->getPeriodRange($period);

        return match ($definition->trigger_type) {
            'order_count' => $this->checkOrderCount($tenantId, $userId, $threshold, $start, $end),
            'revenue_threshold' => $this->checkRevenueThreshold($tenantId, $userId, $threshold, $start, $end),
            'goal_completion' => $this->checkGoalCompletion($tenantId, $userId, $threshold),
            'ranking_position' => $this->checkRankingPosition($tenantId, $userId, $config['position'] ?? 3),
            'streak_days' => $this->checkStreakDays($tenantId, $userId, $config['days'] ?? 7),
            default => false,
        };
    }

    private function checkOrderCount(int $tenantId, int $userId, int $threshold, ?Carbon $start, ?Carbon $end): bool
    {
        $query = SaleOrder::where('tenant_id', $tenantId)
            ->where('approved_by', $userId)
            ->whereIn('status', ['aprovado', 'faturado', 'em_transito', 'entregue']);

        if ($start && $end) {
            $query->whereBetween('ordered_at', [$start, $end]);
        }

        return $query->count() >= $threshold;
    }

    private function checkRevenueThreshold(int $tenantId, int $userId, float $threshold, ?Carbon $start, ?Carbon $end): bool
    {
        $query = SaleOrder::where('tenant_id', $tenantId)
            ->where('approved_by', $userId)
            ->whereIn('status', ['aprovado', 'faturado', 'em_transito', 'entregue']);

        if ($start && $end) {
            $query->whereBetween('ordered_at', [$start, $end]);
        }

        return (float) $query->sum('total') >= $threshold;
    }

    private function checkGoalCompletion(int $tenantId, int $userId, float $threshold): bool
    {
        return SalesGoal::where('tenant_id', $tenantId)
            ->where('goal_type', 'seller')
            ->where('target_user_id', $userId)
            ->where('completion_percent', '>=', $threshold)
            ->exists();
    }

    private function checkRankingPosition(int $tenantId, int $userId, int $maxPosition): bool
    {
        $sellers = SaleOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['aprovado', 'faturado', 'em_transito', 'entregue'])
            ->where('ordered_at', '>=', now()->startOfMonth())
            ->whereNotNull('approved_by')
            ->select('approved_by')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupBy('approved_by')
            ->orderByDesc('revenue')
            ->limit($maxPosition)
            ->pluck('approved_by')
            ->toArray();

        return in_array($userId, $sellers);
    }

    private function checkStreakDays(int $tenantId, int $userId, int $requiredDays): bool
    {
        $profile = $this->profileRepository->findOrCreateForUser($tenantId, $userId);
        return $profile->current_streak_days >= $requiredDays;
    }

    private function unlockAchievement(int $tenantId, int $userId, $definition, ?SaleOrder $order): UserAchievement
    {
        $achievement = UserAchievement::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'achievement_definition_id' => $definition->id,
            'sale_order_id' => $order?->id,
            'metadata' => [
                'trigger_type' => $definition->trigger_type,
                'trigger_config' => $definition->trigger_config,
            ],
        ]);

        if ($definition->points_reward > 0) {
            $profile = $this->profileRepository->findOrCreateForUser($tenantId, $userId);
            $newTotal = $profile->total_points + $definition->points_reward;

            $profile->update([
                'total_points' => $newTotal,
                'achievements_count' => $profile->achievements_count + 1,
            ]);

            DB::table('gamification_point_logs')->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'points' => $definition->points_reward,
                'balance_after' => $newTotal,
                'source_type' => 'badge_unlocked',
                'source_id' => $definition->id,
                'description' => "Badge desbloqueado: {$definition->name}",
                'created_at' => now(),
            ]);
        }

        try {
            $this->notificationService->send(
                $userId,
                $tenantId,
                'achievement_unlocked',
                [
                    'title' => "Conquista desbloqueada: {$definition->name}",
                    'message' => $definition->description ?? "Você desbloqueou a conquista \"{$definition->name}\"!",
                    'achievement_id' => $definition->id,
                    'points_reward' => $definition->points_reward,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Falha ao notificar achievement', ['error' => $e->getMessage()]);
        }

        Log::info('Achievement desbloqueado', [
            'user_id' => $userId,
            'achievement' => $definition->key,
            'points' => $definition->points_reward,
        ]);

        return $achievement;
    }

    private function getPeriodRange(?string $period): array
    {
        if (!$period) {
            return [null, null];
        }

        return match ($period) {
            'day' => [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'quarter' => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => [null, null],
        };
    }
}
