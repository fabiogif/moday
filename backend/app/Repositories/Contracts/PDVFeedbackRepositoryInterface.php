<?php

namespace App\Repositories\Contracts;

use App\Models\PDVFeedback;

interface PDVFeedbackRepositoryInterface
{
    public function createFeedback(array $data): PDVFeedback;
    public function getFeedbacksByTenant(int $tenantId, ?string $type = null, ?string $status = null, int $page = 1, int $perPage = 15): PaginateRepositoryInterface;
    public function getFeedbackByUuid(string $uuid, int $tenantId): ?PDVFeedback;
    public function updateFeedbackStatus(string $uuid, int $tenantId, string $status, ?int $reviewedBy = null, ?string $reviewNotes = null): bool;
}

