<?php

namespace App\Services\Stock;

use App\Exceptions\StockException;
use App\Models\Batch;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FefoAllocationService
{
    /**
     * @return array<int, array{batch_id: int, quantity: float}>
     */
    public function allocate(int $tenantId, int $productId, float $quantity, ?int $warehouseId = null): array
    {
        if ($quantity <= 0) {
            return [];
        }

        $query = Batch::forTenant($tenantId)
            ->where('product_id', $productId)
            ->where('status', 'available')
            ->where('quantity_available', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', Carbon::today());
            })
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        /** @var Collection<int, Batch> $batches */
        $batches = $query->get();
        $remaining = $quantity;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            if ($batch->isExpired()) {
                continue;
            }

            $freeInBatch = (float) $batch->quantity_available - (float) $batch->quantity_reserved;
            if ($freeInBatch <= 0) {
                continue;
            }

            $take = min($remaining, $freeInBatch);
            $allocations[] = [
                'batch_id' => $batch->id,
                'quantity' => $take,
            ];
            $remaining -= $take;
        }

        if ($remaining > 0) {
            $product = Product::find($productId);
            $available = $this->getAvailableQuantity($tenantId, $productId, $warehouseId);
            throw StockException::insufficientStock(
                $product?->name ?? "produto #{$productId}",
                $quantity,
                $available
            );
        }

        return $allocations;
    }

    public function getAvailableQuantity(int $tenantId, int $productId, ?int $warehouseId = null): float
    {
        $query = Batch::forTenant($tenantId)
            ->where('product_id', $productId)
            ->where('status', 'available')
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', Carbon::today());
            });

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (float) $query->get()->sum(
            fn (Batch $b) => max(0, (float) $b->quantity_available - (float) $b->quantity_reserved)
        );
    }
}
