<?php

namespace App\Repositories;

use App\Models\ReorderSuggestion;
use App\Repositories\Contracts\ReorderSuggestionRepositoryInterface;
use Illuminate\Support\Collection;

class ReorderSuggestionRepository implements ReorderSuggestionRepositoryInterface
{
    public function listPendingForTenant(int $tenantId): Collection
    {
        return ReorderSuggestion::where('tenant_id', $tenantId)
            ->where('is_dismissed', false)
            ->with(['product:id,name,sku,unit_of_measure,qtd_stock,min_stock,safety_stock', 'supplier:id,name'])
            // CASE é portável (MySQL + SQLite); FIELD() não existe no SQLite
            ->orderByRaw("CASE urgency WHEN 'critical' THEN 1 WHEN 'urgent' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderByDesc('suggested_quantity')
            ->get();
    }

    public function findForTenant(int $tenantId, int $id): ReorderSuggestion
    {
        return ReorderSuggestion::where('tenant_id', $tenantId)->findOrFail($id);
    }

    public function upsert(int $tenantId, int $productId, array $data): ReorderSuggestion
    {
        return ReorderSuggestion::updateOrCreate(
            ['tenant_id' => $tenantId, 'product_id' => $productId],
            $data
        );
    }
}
