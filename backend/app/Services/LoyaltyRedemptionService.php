<?php

namespace App\Services;

use App\Repositories\Contracts\LoyaltyRewardRepositoryInterface;
use App\Repositories\Contracts\LoyaltyTransactionRepositoryInterface;
use App\Repositories\Contracts\LoyaltyRedemptionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyRedemptionService
{
    public function __construct(
        private readonly LoyaltyRewardRepositoryInterface $rewardRepository,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly LoyaltyRedemptionRepositoryInterface $redemptionRepository
    ) {}

    /**
     * Resgata uma recompensa
     */
    public function redeemReward(int $clientId, int $rewardId): array
    {
        DB::beginTransaction();
        try {
            $reward = $this->rewardRepository->getById($rewardId);

            if (!$reward) {
                throw new \Exception('Recompensa não encontrada.');
            }

            // Validações
            $this->validateRedemption($reward, $clientId);

            $programId = $reward->loyalty_program_id;
            $currentBalance = $this->transactionRepository->getBalance($clientId, $programId);

            // Criar transação de resgate
            $transaction = $this->transactionRepository->create([
                'loyalty_program_id' => $programId,
                'client_id' => $clientId,
                'type' => 'redeem',
                'points' => -$reward->points_required,
                'balance_after' => $currentBalance - $reward->points_required,
                'loyalty_reward_id' => $rewardId,
                'description' => "Resgatou: {$reward->name}",
            ]);

            // Calcular validade
            $expiresAt = $reward->validity_days
                ? now()->addDays($reward->validity_days)->format('Y-m-d')
                : null;

            // Criar resgate
            $redemption = $this->redemptionRepository->create([
                'loyalty_reward_id' => $rewardId,
                'client_id' => $clientId,
                'loyalty_transaction_id' => $transaction->id,
                'points_used' => $reward->points_required,
                'status' => 'pending',
                'redeemed_at' => now()->format('Y-m-d'),
                'expires_at' => $expiresAt,
            ]);

            // Decrementar estoque
            $reward->decrementStock();

            DB::commit();
            Log::info('Recompensa resgatada', [
                'client_id' => $clientId,
                'reward_id' => $rewardId,
                'redemption_id' => $redemption->id
            ]);

            return [
                'redemption' => $redemption,
                'coupon_code' => $redemption->coupon_code,
                'expires_at' => $expiresAt,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao resgatar recompensa: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valida se o resgate é possível
     */
    private function validateRedemption($reward, int $clientId): void
    {
        // Recompensa ativa e disponível
        if (!$reward->isAvailable()) {
            throw new \Exception('Esta recompensa não está disponível no momento.');
        }

        // Tem estoque
        if (!$reward->hasStock()) {
            throw new \Exception('Esta recompensa está esgotada.');
        }

        // Cliente tem pontos suficientes
        $availablePoints = $this->transactionRepository->getAvailablePoints(
            $clientId,
            $reward->loyalty_program_id
        );

        if ($availablePoints < $reward->points_required) {
            throw new \Exception("Pontos insuficientes. Você tem {$availablePoints}, precisa de {$reward->points_required}.");
        }

        // Verificar limite por usuário
        if (!$reward->canBeRedeemedBy($clientId)) {
            throw new \Exception('Você atingiu o limite de resgates para esta recompensa.');
        }
    }

    /**
     * Marca resgate como usado
     */
    public function markRedemptionAsUsed(string $couponCode, int $orderId): void
    {
        DB::beginTransaction();
        try {
            $redemption = $this->redemptionRepository->getByUuid($couponCode);

            if (!$redemption) {
                throw new \Exception('Cupom não encontrado.');
            }

            if ($redemption->status !== 'pending') {
                throw new \Exception('Este cupom já foi utilizado ou expirou.');
            }

            if ($redemption->isExpired()) {
                throw new \Exception('Este cupom expirou.');
            }

            $redemption->markAsUsed($orderId);

            DB::commit();
            Log::info('Resgate marcado como usado', ['coupon_code' => $couponCode, 'order_id' => $orderId]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao marcar resgate como usado: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtém resgates de um cliente
     */
    public function getClientRedemptions(int $clientId)
    {
        return $this->redemptionRepository->getByClient($clientId);
    }

    /**
     * Expira resgates pendentes vencidos
     */
    public function expireRedemptions(): int
    {
        DB::beginTransaction();
        try {
            $expired = $this->redemptionRepository->getPendingExpired();

            foreach ($expired as $redemption) {
                $redemption->markAsExpired();
            }

            $count = $expired->count();

            DB::commit();
            Log::info('Resgates expirados', ['count' => $count]);

            return $count;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao expirar resgates: ' . $e->getMessage());
            throw $e;
        }
    }
}

