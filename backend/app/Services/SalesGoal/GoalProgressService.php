<?php

namespace App\Services\SalesGoal;

use App\Models\SaleOrder;
use App\Models\SalesGoal;
use App\Models\SalesGoalProgressLog;
use App\Repositories\Contracts\SalesGoalRepositoryInterface;
use App\Services\CacheService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoalProgressService
{
    private const COUNTABLE_STATUSES = ['aprovado', 'faturado', 'em_transito', 'entregue'];
    private const MILESTONES = [50, 75, 100];

    public function __construct(
        private readonly SalesGoalRepositoryInterface $salesGoalRepository,
        private readonly NotificationService $notificationService,
        private readonly CacheService $cacheService
    ) {}

    public function processOrderForGoals(SaleOrder $order, string $eventType): void
    {
        if (!$order->tenant_id || !$order->approved_by) {
            return;
        }

        $productIds = $order->items->pluck('product_id')->unique()->filter()->toArray();

        $goals = $this->salesGoalRepository->getActiveGoalsMatchingOrder(
            $order->tenant_id,
            $order->approved_by,
            $productIds[0] ?? null
        );

        foreach ($goals as $goal) {
            if ($this->goalMatchesOrder($goal, $order)) {
                $this->recalculateGoal($goal, $order, $eventType);
            }
        }

        $this->cacheService->invalidateSalesGoalCache($order->tenant_id);
    }

    public function recalculateGoalManually(SalesGoal $goal): void
    {
        $newValue = $this->calculateGoalValue($goal);
        $previousValue = (float) $goal->current_value;

        $percent = $goal->target_value > 0
            ? min(round(($newValue / $goal->target_value) * 100, 2), 100)
            : 0;

        $status = $newValue >= $goal->target_value ? 'completed' : $goal->status;

        $goal->update([
            'current_value' => $newValue,
            'completion_percent' => $percent,
            'status' => $status,
        ]);

        SalesGoalProgressLog::create([
            'sales_goal_id' => $goal->id,
            'previous_value' => $previousValue,
            'added_value' => $newValue - $previousValue,
            'new_value' => $newValue,
            'event_type' => 'manual_adjustment',
        ]);

        $this->cacheService->invalidateSalesGoalCache($goal->tenant_id);

        Log::info('Meta recalculada manualmente', ['goal_id' => $goal->id, 'new_value' => $newValue]);
    }

    private function recalculateGoal(SalesGoal $goal, SaleOrder $order, string $eventType): void
    {
        DB::transaction(function () use ($goal, $order, $eventType) {
            $goal = SalesGoal::lockForUpdate()->find($goal->id);
            if (!$goal || $goal->status !== 'active') {
                return;
            }

            $previousValue = (float) $goal->current_value;
            $previousPercent = (float) $goal->completion_percent;
            $newValue = $this->calculateGoalValue($goal);

            $percent = $goal->target_value > 0
                ? min(round(($newValue / $goal->target_value) * 100, 2), 100)
                : 0;

            $status = $newValue >= $goal->target_value ? 'completed' : 'active';

            $goal->update([
                'current_value' => $newValue,
                'completion_percent' => $percent,
                'status' => $status,
            ]);

            SalesGoalProgressLog::create([
                'sales_goal_id' => $goal->id,
                'sale_order_id' => $order->id,
                'previous_value' => $previousValue,
                'added_value' => $newValue - $previousValue,
                'new_value' => $newValue,
                'event_type' => $eventType,
            ]);

            $this->checkMilestones($goal, $previousPercent, $percent);
        });
    }

    private function calculateGoalValue(SalesGoal $goal): float
    {
        $query = SaleOrder::where('tenant_id', $goal->tenant_id)
            ->whereIn('status', self::COUNTABLE_STATUSES)
            ->where('ordered_at', '>=', $goal->period_start)
            ->where('ordered_at', '<=', $goal->period_end);

        switch ($goal->goal_type) {
            case 'seller':
                $query->where('approved_by', $goal->target_user_id);
                break;

            case 'product':
                $query->whereHas('items', function ($q) use ($goal) {
                    $q->where('product_id', $goal->target_product_id);
                });
                if ($goal->goal_type === 'product') {
                    return (float) $query->get()->sum(function ($order) use ($goal) {
                        return $order->items->where('product_id', $goal->target_product_id)->sum(function ($item) {
                            return $item->quantity * $item->unit_price;
                        });
                    });
                }
                break;

            case 'team':
            case 'region':
                $userIds = DB::table('user_profiles')
                    ->where('profile_id', $goal->target_profile_id)
                    ->pluck('user_id');
                $query->whereIn('approved_by', $userIds);
                break;
        }

        return (float) $query->sum('total');
    }

    private function goalMatchesOrder(SalesGoal $goal, SaleOrder $order): bool
    {
        $orderDate = $order->ordered_at ?? $order->created_at;
        if ($orderDate < $goal->period_start || $orderDate > $goal->period_end) {
            return false;
        }

        switch ($goal->goal_type) {
            case 'seller':
                return $goal->target_user_id === $order->approved_by;

            case 'product':
                $orderProductIds = $order->items->pluck('product_id')->toArray();
                return in_array($goal->target_product_id, $orderProductIds);

            case 'team':
            case 'region':
                $userIds = DB::table('user_profiles')
                    ->where('profile_id', $goal->target_profile_id)
                    ->pluck('user_id')
                    ->toArray();
                return in_array($order->approved_by, $userIds);
        }

        return false;
    }

    private function checkMilestones(SalesGoal $goal, float $previousPercent, float $currentPercent): void
    {
        $notifyUserId = $goal->target_user_id ?? $goal->created_by;
        if (!$notifyUserId) {
            return;
        }

        foreach (self::MILESTONES as $milestone) {
            if ($previousPercent < $milestone && $currentPercent >= $milestone) {
                try {
                    $this->notificationService->send(
                        $notifyUserId,
                        $goal->tenant_id,
                        'goal_milestone',
                        [
                            'title' => $milestone === 100
                                ? "Meta atingida: {$goal->title}"
                                : "Meta {$milestone}%: {$goal->title}",
                            'message' => $milestone === 100
                                ? "Parabéns! A meta \"{$goal->title}\" foi concluída com sucesso!"
                                : "A meta \"{$goal->title}\" atingiu {$milestone}% do objetivo.",
                            'goal_id' => $goal->id,
                            'milestone' => $milestone,
                        ]
                    );
                } catch (\Exception $e) {
                    Log::warning('Falha ao enviar notificação de marco', [
                        'goal_id' => $goal->id,
                        'milestone' => $milestone,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
