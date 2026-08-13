<?php

namespace App\Repositories;

use App\Models\LoyaltyTransaction;
use App\Repositories\Contracts\LoyaltyTransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class LoyaltyTransactionRepository implements LoyaltyTransactionRepositoryInterface
{
    protected LoyaltyTransaction $entity;

    public function __construct(LoyaltyTransaction $loyaltyTransaction)
    {
        $this->entity = $loyaltyTransaction;
    }

    public function getByClient(int $clientId, int $programId)
    {
        return $this->entity
            ->where('client_id', $clientId)
            ->where('loyalty_program_id', $programId)
            ->with(['order', 'loyaltyReward'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getBalance(int $clientId, int $programId): int
    {
        $lastTransaction = $this->entity
            ->where('client_id', $clientId)
            ->where('loyalty_program_id', $programId)
            ->latest()
            ->first();

        return $lastTransaction ? $lastTransaction->balance_after : 0;
    }

    public function getAvailablePoints(int $clientId, int $programId): int
    {
        // Pontos disponíveis = pontos ganhos ativos - pontos resgatados
        $earned = $this->entity
            ->where('client_id', $clientId)
            ->where('loyalty_program_id', $programId)
            ->where('type', 'earn')
            ->active()
            ->sum('points');

        $redeemed = $this->entity
            ->where('client_id', $clientId)
            ->where('loyalty_program_id', $programId)
            ->where('type', 'redeem')
            ->sum(DB::raw('ABS(points)'));

        return max(0, $earned - $redeemed);
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function getExpiredPoints(int $programId)
    {
        return $this->entity
            ->where('loyalty_program_id', $programId)
            ->expired()
            ->get();
    }

    public function markPointsAsExpired(array $transactionIds): void
    {
        // Cria transações de expiração
        foreach ($transactionIds as $transactionId) {
            $transaction = $this->entity->find($transactionId);
            if ($transaction && $transaction->type === 'earn') {
                $this->create([
                    'loyalty_program_id' => $transaction->loyalty_program_id,
                    'client_id' => $transaction->client_id,
                    'type' => 'expire',
                    'points' => -$transaction->points,
                    'balance_after' => $this->getBalance($transaction->client_id, $transaction->loyalty_program_id) - $transaction->points,
                    'description' => 'Pontos expirados',
                ]);
            }
        }
    }
}

