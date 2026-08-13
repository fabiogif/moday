<?php

namespace App\Repositories\Contracts;

use App\Models\ReorderSuggestion;
use Illuminate\Support\Collection;

interface ReorderSuggestionRepositoryInterface
{
    public function listPendingForTenant(int $tenantId): Collection;

    public function findForTenant(int $tenantId, int $id): ReorderSuggestion;

    public function upsert(int $tenantId, int $productId, array $data): ReorderSuggestion;
}
