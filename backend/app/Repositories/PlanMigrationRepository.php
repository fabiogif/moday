<?php

namespace App\Repositories;

use App\Models\PlanMigration;
use App\Repositories\Contracts\PlanMigrationRepositoryInterface;

class PlanMigrationRepository implements PlanMigrationRepositoryInterface
{
    public function createRecord(array $data): mixed
    {
        return PlanMigration::create($data);
    }

    public function getFilteredHistory(array $filters = [], int $limit = 50): mixed
    {
        $query = PlanMigration::with(['tenant', 'fromPlan', 'toPlan'])
            ->orderByDesc('migrated_at');

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['plan_id'])) {
            $planId = $filters['plan_id'];
            $query->where(function ($q) use ($planId) {
                $q->where('from_plan_id', $planId)
                    ->orWhere('to_plan_id', $planId);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('migrated_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('migrated_at', '<=', $filters['date_to']);
        }

        return $query->limit($limit)->get();
    }

    public function getHistoryForTenant(int $tenantId, ?int $limit = 10): array
    {
        $migrations = PlanMigration::where('tenant_id', $tenantId)
            ->with(['fromPlan', 'toPlan'])
            ->orderBy('migrated_at', 'desc')
            ->limit($limit)
            ->get();

        return $migrations->map(fn ($migration) => [
            'id' => $migration->id,
            'from_plan' => $migration->fromPlan ? $migration->fromPlan->name : 'Sem plano',
            'to_plan' => $migration->toPlan->name,
            'status' => $migration->status,
            'migrated_at' => $migration->migrated_at->toIso8601String(),
            'notes' => $migration->notes,
        ])->toArray();
    }
}
