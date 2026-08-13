<?php

namespace App\Services\Commercial;

use App\Models\Client;
use App\Models\PriceTable;
use App\Models\PriceTableItem;
use App\Models\Product;
use Illuminate\Support\Collection;

class PriceTableService
{
    public function resolveUnitPrice(?int $clientId, int $productId, float $quantity, ?float $fallbackPrice = null): float
    {
        $price = $this->lookupPrice($clientId, $productId, $quantity);

        if ($price !== null) {
            return $price;
        }

        if ($fallbackPrice !== null) {
            return $fallbackPrice;
        }

        $product = Product::find($productId);

        return (float) ($product?->price ?? 0);
    }

    public function lookupPrice(?int $clientId, int $productId, float $quantity = 1): ?float
    {
        $tableId = null;

        if ($clientId) {
            $client = Client::find($clientId);
            $tableId = $client?->price_table_id;
        }

        if (!$tableId) {
            $client = $clientId ? Client::find($clientId) : null;
            $tableId = PriceTable::where('tenant_id', $client?->tenant_id)
                ->active()
                ->where('type', 'padrao')
                ->value('id');
        }

        if (!$tableId) {
            return null;
        }

        $table = PriceTable::find($tableId);
        if (!$table || !$table->is_active) {
            return null;
        }

        return $table->getPriceForProduct($productId, $quantity);
    }

    /**
     * @param array<int, array{product_id: int, quantity: float, unit_price?: float, item_type?: string}> $items
     * @return array<int, array{product_id: int, quantity: float, unit_price: float, item_type?: string}>
     */
    public function applyPricesToItems(?int $clientId, array $items): array
    {
        return array_map(function (array $item) use ($clientId) {
            if (($item['item_type'] ?? 'venda') === 'bonificacao') {
                $item['unit_price'] = 0;
                return $item;
            }

            if (!isset($item['unit_price']) || $item['unit_price'] === null || $item['unit_price'] === '') {
                $item['unit_price'] = $this->resolveUnitPrice(
                    $clientId,
                    (int) $item['product_id'],
                    (float) $item['quantity'],
                );
            }

            return $item;
        }, $items);
    }

    public function listForTenant(int $tenantId): Collection
    {
        return PriceTable::forTenant($tenantId)
            ->withCount('items')
            ->orderBy('name')
            ->get();
    }

    public function create(int $tenantId, array $data): PriceTable
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $table = PriceTable::create(array_merge($data, ['tenant_id' => $tenantId]));

        foreach ($items as $item) {
            PriceTableItem::create([
                'price_table_id' => $table->id,
                'product_id'     => $item['product_id'],
                'price'          => $item['price'],
                'min_quantity'   => $item['min_quantity'] ?? 1,
            ]);
        }

        return $table->load('items.product:id,name,sku');
    }

    public function update(PriceTable $table, array $data): PriceTable
    {
        $items = $data['items'] ?? null;
        unset($data['items']);

        $table->update($data);

        if (is_array($items)) {
            $table->items()->delete();
            foreach ($items as $item) {
                PriceTableItem::create([
                    'price_table_id' => $table->id,
                    'product_id'     => $item['product_id'],
                    'price'          => $item['price'],
                    'min_quantity'   => $item['min_quantity'] ?? 1,
                ]);
            }
        }

        return $table->fresh(['items.product:id,name,sku']);
    }
}
