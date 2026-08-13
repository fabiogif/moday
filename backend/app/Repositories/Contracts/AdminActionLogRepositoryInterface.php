<?php

namespace App\Repositories\Contracts;

interface AdminActionLogRepositoryInterface
{
    public function logAction(
        $admin,
        string $action,
        string $description,
        $tenant = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void;

    public function createForceDeleteLog($admin, string $tenantName): void;

    public function getEmailHistory(int $limit = 50): mixed;
}
