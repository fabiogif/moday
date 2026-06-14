<?php

namespace App\Repositories\Contracts;

interface LoyaltyRedemptionRepositoryInterface
{
    public function getByClient(int $clientId);
    public function getByUuid(string $uuid);
    public function create(array $data);
    public function update(int $id, array $data);
    public function getPendingExpired();
    public function countByClientAndReward(int $clientId, int $rewardId): int;
}

