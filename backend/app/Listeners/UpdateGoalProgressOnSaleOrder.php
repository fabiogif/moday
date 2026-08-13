<?php

namespace App\Listeners;

use App\Events\SaleOrderConfirmedEvent;
use App\Events\SaleOrderStatusChangedEvent;
use App\Services\SalesGoal\AchievementService;
use App\Services\SalesGoal\GamificationService;
use App\Services\SalesGoal\GoalProgressService;
use Illuminate\Support\Facades\Log;

class UpdateGoalProgressOnSaleOrder
{
    public function __construct(
        private readonly GoalProgressService $goalProgressService,
        private readonly GamificationService $gamificationService,
        private readonly AchievementService $achievementService
    ) {}

    public function handle(SaleOrderConfirmedEvent|SaleOrderStatusChangedEvent $event): void
    {
        try {
            $order = $event->order;

            if ($event instanceof SaleOrderStatusChangedEvent) {
                $eventType = match ($event->newStatus) {
                    'aprovado' => 'order_confirmed',
                    'entregue' => 'order_delivered',
                    'cancelado' => 'order_cancelled',
                    default => 'order_confirmed',
                };
            } else {
                $eventType = 'order_confirmed';
            }

            $order->loadMissing('items');

            $this->goalProgressService->processOrderForGoals($order, $eventType);

            if ($order->approved_by && $eventType !== 'order_cancelled') {
                $this->gamificationService->awardPointsForSale($order->tenant_id, $order->approved_by, $order);
                $this->gamificationService->updateStreak($order->tenant_id, $order->approved_by);
                $this->achievementService->checkAndUnlockAchievements($order->tenant_id, $order->approved_by, $order);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao processar progresso de meta: ' . $e->getMessage(), [
                'order_id' => $event->order->id ?? null,
            ]);
        }
    }
}
