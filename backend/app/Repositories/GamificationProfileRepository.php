<?php

namespace App\Repositories;

use App\Models\GamificationProfile;
use App\Repositories\Contracts\GamificationProfileRepositoryInterface;

class GamificationProfileRepository implements GamificationProfileRepositoryInterface
{
    protected GamificationProfile $entity;

    public function __construct(GamificationProfile $gamificationProfile)
    {
        $this->entity = $gamificationProfile;
    }

    public function findOrCreateForUser(int $tenantId, int $userId)
    {
        return $this->entity->firstOrCreate(
            ['user_id' => $userId],
            [
                'tenant_id' => $tenantId,
                'total_points' => 0,
                'current_streak_days' => 0,
                'best_streak_days' => 0,
                'achievements_count' => 0,
            ]
        );
    }

    public function getLeaderboard(int $tenantId, int $limit = 20)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('total_points', '>', 0)
            ->with('user:id,name,avatar')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get();
    }
}
