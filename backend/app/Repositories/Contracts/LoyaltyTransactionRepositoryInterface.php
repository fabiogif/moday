<?php

namespace App\Repositories\Contracts;

interface LoyaltyTransactionRepositoryInterface
{
    public function getByClient(int $clientId, int $programId);
    public function getBalance(int $clientId, int $programId): int;
    public function getAvailablePoints(int $clientId, int $programId): int;
    public function create(array $data);
    public function getExpiredPoints(int $programId);
    public function markPointsAsExpired(array $transactionIds): void;
}

