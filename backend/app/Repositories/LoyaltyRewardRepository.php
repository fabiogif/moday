<?php

namespace App\Repositories;

use App\Models\LoyaltyReward;
use App\Repositories\Contracts\LoyaltyRewardRepositoryInterface;

class LoyaltyRewardRepository implements LoyaltyRewardRepositoryInterface
{
    protected LoyaltyReward $entity;

    public function __construct(LoyaltyReward $loyaltyReward)
    {
        $this->entity = $loyaltyReward;
    }

    public function getAllByProgram(int $programId)
    {
        return $this->entity
            ->where('loyalty_program_id', $programId)
            ->with('product')
            ->orderBy('points_required')
            ->get();
    }

    public function getAvailableByProgram(int $programId)
    {
        return $this->entity
            ->where('loyalty_program_id', $programId)
            ->available()
            ->inStock()
            ->with('product')
            ->orderBy('points_required')
            ->get();
    }

    public function getById(int $id)
    {
        return $this->entity->with('product', 'loyaltyProgram')->find($id);
    }

    public function getByUuid(string $uuid)
    {
        return $this->entity->with('product', 'loyaltyProgram')->where('uuid', $uuid)->first();
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $reward = $this->getById($id);
        if ($reward) {
            $reward->update($data);
            return $reward->fresh(['product', 'loyaltyProgram']);
        }
        return null;
    }

    public function delete(int $id)
    {
        $reward = $this->getById($id);
        if ($reward) {
            return $reward->delete();
        }
        return false;
    }
}

