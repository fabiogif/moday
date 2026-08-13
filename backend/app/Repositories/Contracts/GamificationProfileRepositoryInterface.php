<?php

namespace App\Repositories\Contracts;

interface GamificationProfileRepositoryInterface
{
    public function findOrCreateForUser(int $tenantId, int $userId);
    public function getLeaderboard(int $tenantId, int $limit = 20);
}
