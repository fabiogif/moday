<?php

namespace App\Repositories;

use App\Models\LoyaltyRedemption;
use App\Repositories\Contracts\LoyaltyRedemptionRepositoryInterface;

class LoyaltyRedemptionRepository implements LoyaltyRedemptionRepositoryInterface
{
    protected LoyaltyRedemption $entity;

    public function __construct(LoyaltyRedemption $loyaltyRedemption)
    {
        $this->entity = $loyaltyRedemption;
    }

    public function getByClient(int $clientId)
    {
        return $this->entity
            ->where('client_id', $clientId)
            ->with(['loyaltyReward', 'loyaltyTransaction', 'order'])
            ->orderBy('redeemed_at', 'desc')
            ->get();
    }

    public function getByUuid(string $uuid)
    {
        return $this->entity
            ->with(['loyaltyReward', 'loyaltyTransaction', 'order', 'client'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $redemption = $this->entity->find($id);
        if ($redemption) {
            $redemption->update($data);
            return $redemption->fresh(['loyaltyReward', 'loyaltyTransaction', 'order']);
        }
        return null;
    }

    public function getPendingExpired()
    {
        return $this->entity
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->format('Y-m-d'))
            ->get();
    }

    public function countByClientAndReward(int $clientId, int $rewardId): int
    {
        return $this->entity
            ->where('client_id', $clientId)
            ->where('loyalty_reward_id', $rewardId)
            ->whereIn('status', ['pending', 'used'])
            ->count();
    }
}

