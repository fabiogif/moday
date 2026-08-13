<?php

namespace App\Services\Audit;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PriceHistoryService
{
    private const TRACKED_FIELDS = ['price', 'price_cost', 'promotional_price', 'price_minimum'];

    public function recordChanges(Product $product, ?int $changedBy = null, ?string $reason = null): int
    {
        $recorded = 0;

        foreach (self::TRACKED_FIELDS as $field) {
            $old = $product->getOriginal($field);
            $new = $product->getAttribute($field);

            if ($old === null && $new === null) continue;
            if ((float) $old === (float) $new) continue;

            $changePct = ($old && (float) $old > 0)
                ? round((((float) $new - (float) $old) / (float) $old) * 100, 2)
                : null;

            PriceHistory::create([
                'tenant_id'  => $product->tenant_id,
                'product_id' => $product->id,
                'changed_by' => $changedBy,
                'field'      => $field,
                'old_value'  => $old,
                'new_value'  => $new,
                'change_pct' => $changePct,
                'reason'     => $reason,
            ]);

            $recorded++;
        }

        return $recorded;
    }

    public function listForProduct(int $tenantId, int $productId, int $perPage = 30): LengthAwarePaginator
    {
        return PriceHistory::forTenant($tenantId)
            ->forProduct($productId)
            ->with('changedBy:id,name')
            ->latest()
            ->paginate($perPage);
    }

    public function listForTenant(int $tenantId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = PriceHistory::forTenant($tenantId)
            ->with(['product:id,name,sku', 'changedBy:id,name'])
            ->latest();

        if (!empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }
        if (!empty($filters['field'])) {
            $query->where('field', $filters['field']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    public function summarizeVolatility(int $tenantId, int $days = 30): Collection
    {
        return PriceHistory::forTenant($tenantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('product_id, field, COUNT(*) as changes, AVG(ABS(change_pct)) as avg_change_pct')
            ->groupBy('product_id', 'field')
            ->with('product:id,name,sku')
            ->orderByDesc('changes')
            ->limit(20)
            ->get();
    }
}
