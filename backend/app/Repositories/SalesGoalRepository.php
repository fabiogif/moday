<?php

namespace App\Repositories;

use App\Models\SalesGoal;
use App\Repositories\Contracts\SalesGoalRepositoryInterface;

class SalesGoalRepository implements SalesGoalRepositoryInterface
{
    protected SalesGoal $entity;

    public function __construct(SalesGoal $salesGoal)
    {
        $this->entity = $salesGoal;
    }

    public function findAllForTenant(int $tenantId, array $filters = [])
    {
        $query = $this->entity
            ->where('tenant_id', $tenantId)
            ->with(['targetUser', 'targetProfile', 'targetProduct', 'createdByUser']);

        if (!empty($filters['goal_type'])) {
            $query->where('goal_type', $filters['goal_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['period_type'])) {
            $query->where('period_type', $filters['period_type']);
        }

        if (!empty($filters['target_user_id'])) {
            $query->where('target_user_id', $filters['target_user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function findForTenant(int $tenantId, int $id, array $with = [])
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with(array_merge(['targetUser', 'targetProfile', 'targetProduct', 'createdByUser'], $with))
            ->first();
    }

    public function findByUuidAndTenant(int $tenantId, string $uuid, array $with = [])
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('uuid', $uuid)
            ->with(array_merge(['targetUser', 'targetProfile', 'targetProduct', 'createdByUser'], $with))
            ->first();
    }

    public function getById(int $id)
    {
        return $this->entity->find($id);
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $goal = $this->getById($id);
        if ($goal) {
            $goal->update($data);
            return $goal->fresh(['targetUser', 'targetProfile', 'targetProduct', 'createdByUser']);
        }
        return null;
    }

    public function delete(int $id)
    {
        $goal = $this->getById($id);
        if ($goal) {
            return $goal->delete();
        }
        return false;
    }

    public function getActiveGoalsForSeller(int $tenantId, int $userId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('goal_type', 'seller')
            ->where('target_user_id', $userId)
            ->get();
    }

    public function getActiveGoalsMatchingOrder(int $tenantId, int $userId, ?int $productId)
    {
        $now = now()->toDateString();

        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('period_start', '<=', $now)
            ->where('period_end', '>=', $now)
            ->where(function ($query) use ($userId, $productId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('goal_type', 'seller')
                      ->where('target_user_id', $userId);
                });

                if ($productId) {
                    $query->orWhere(function ($q) use ($productId) {
                        $q->where('goal_type', 'product')
                          ->where('target_product_id', $productId);
                    });
                }

                $query->orWhereIn('goal_type', ['team', 'region']);
            })
            ->get();
    }
}
