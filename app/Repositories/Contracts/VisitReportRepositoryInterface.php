<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface VisitReportRepositoryInterface
{
    /**
     * @return Collection<int, object{status: string, total: int}>
     */
    public function countsByStatus(int $tenantId, string $dateFrom, string $dateTo, User $requestingUser, ?int $userId = null): Collection;

    /**
     * @return Collection<int, object{type: string, total: int}>
     */
    public function countsByType(int $tenantId, string $dateFrom, string $dateTo, User $requestingUser, ?int $userId = null): Collection;

    /**
     * @return Collection<int, object{priority: string, total: int}>
     */
    public function countsByPriority(int $tenantId, string $dateFrom, string $dateTo, User $requestingUser, ?int $userId = null): Collection;

    /**
     * @return Collection<int, object{user_id: int, total: int, completed: int, sales_closed: int, order_value: float}>
     */
    public function countsBySeller(int $tenantId, string $dateFrom, string $dateTo, User $requestingUser): Collection;
}
