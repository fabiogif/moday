<?php

namespace App\Repositories\contracts;

interface CategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUuid(string $identify);
    public function getStats(int $tenantId): array;
}
