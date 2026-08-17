<?php

namespace App\Repositories\Contracts;

interface PlanMigrationRepositoryInterface
{
    public function createRecord(array $data): mixed;

    public function getFilteredHistory(array $filters = [], int $limit = 50): mixed;

    public function getHistoryForTenant(int $tenantId, ?int $limit = 10): array;
}
