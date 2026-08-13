<?php

namespace App\Services;

use App\Repositories\Contracts\LoyaltyProgramRepositoryInterface;
use App\Repositories\Contracts\LoyaltyTransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyPointsService
{
    public function __construct(
        private readonly LoyaltyProgramRepositoryInterface $programRepository,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository
    ) {}

    /**
     * Adiciona pontos para um cliente baseado em uma compra
     */
    public function addPointsFromPurchase(int $clientId, int $programId, float $amount, int $orderId = null): ?int
    {
        DB::beginTransaction();
        try {
            $program = $this->programRepository->getById($programId);
            
            if (!$program || !$program->is_active) {
                throw new \Exception('Programa de fidelidade não está ativo.');
            }

            // Calcular pontos
            $multiplier = $this->getMultiplier($program);
            $points = $program->calculatePoints($amount, $multiplier);

            if ($points <= 0) {
                Log::info('Nenhum ponto ganho', ['client_id' => $clientId, 'amount' => $amount]);
                DB::commit();
                return 0;
            }

            // Calcular expiração
            $expiresAt = $program->points_expiry_days 
                ? now()->addDays($program->points_expiry_days)->format('Y-m-d')
                : null;

            // Criar transação
            $currentBalance = $this->transactionRepository->getBalance($clientId, $programId);
            
            $transaction = $this->transactionRepository->create([
                'loyalty_program_id' => $programId,
                'client_id' => $clientId,
                'type' => 'earn',
                'points' => $points,
                'balance_after' => $currentBalance + $points,
                'order_id' => $orderId,
                'purchase_amount' => $amount,
                'multiplier' => $multiplier,
                'description' => "Ganhou {$points} pontos na compra",
                'expires_at' => $expiresAt,
            ]);

            DB::commit();
            Log::info('Pontos adicionados', [
                'client_id' => $clientId,
                'points' => $points,
                'transaction_id' => $transaction->id
            ]);

            return $points;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao adicionar pontos: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ajusta pontos manualmente (positivo ou negativo)
     */
    public function adjustPoints(int $clientId, int $programId, int $points, string $description): void
    {
        DB::beginTransaction();
        try {
            $currentBalance = $this->transactionRepository->getBalance($clientId, $programId);
            $newBalance = $currentBalance + $points;

            if ($newBalance < 0) {
                throw new \Exception('Saldo de pontos não pode ficar negativo.');
            }

            $this->transactionRepository->create([
                'loyalty_program_id' => $programId,
                'client_id' => $clientId,
                'type' => 'adjust',
                'points' => $points,
                'balance_after' => $newBalance,
                'description' => $description,
            ]);

            DB::commit();
            Log::info('Pontos ajustados', ['client_id' => $clientId, 'points' => $points]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao ajustar pontos: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtém saldo de pontos de um cliente
     */
    public function getClientBalance(int $clientId, int $programId): int
    {
        return $this->transactionRepository->getBalance($clientId, $programId);
    }

    /**
     * Obtém pontos disponíveis (não expirados)
     */
    public function getAvailablePoints(int $clientId, int $programId): int
    {
        return $this->transactionRepository->getAvailablePoints($clientId, $programId);
    }

    /**
     * Obtém histórico de transações
     */
    public function getTransactionHistory(int $clientId, int $programId)
    {
        return $this->transactionRepository->getByClient($clientId, $programId);
    }

    /**
     * Calcula multiplicador baseado em data/aniversário
     */
    private function getMultiplier($program): float
    {
        $multiplier = 1.0;
        $today = now();

        // Multiplicador por dia da semana
        $dayMultiplier = $program->getMultiplierForDate($today);
        if ($dayMultiplier > 1.0) {
            $multiplier = $dayMultiplier;
        }

        // TODO: Adicionar multiplicador de aniversário se tiver data de nascimento do cliente

        return $multiplier;
    }

    /**
     * Expira pontos vencidos
     */
    public function expirePoints(int $programId): int
    {
        DB::beginTransaction();
        try {
            $expiredTransactions = $this->transactionRepository->getExpiredPoints($programId);
            
            if ($expiredTransactions->isEmpty()) {
                DB::commit();
                return 0;
            }

            $transactionIds = $expiredTransactions->pluck('id')->toArray();
            $this->transactionRepository->markPointsAsExpired($transactionIds);

            $count = count($transactionIds);
            
            DB::commit();
            Log::info('Pontos expirados', ['count' => $count, 'program_id' => $programId]);

            return $count;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao expirar pontos: ' . $e->getMessage());
            throw $e;
        }
    }
}

