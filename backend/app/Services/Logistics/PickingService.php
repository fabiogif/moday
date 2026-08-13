<?php

namespace App\Services\Logistics;

use App\Exceptions\StockException;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class PickingService
{
    public function getPickingList(SaleOrder $order): array
    {
        $order->load(['items.product', 'items.batch', 'items' => function ($q) {
            $q->with(['product:id,name,sku,unit_of_measure']);
        }]);

        $reservations = StockReservation::where('sale_order_id', $order->id)
            ->where('status', 'reserved')
            ->with('batch:id,batch_number,expiry_date')
            ->get()
            ->groupBy('sale_order_item_id');

        return $order->items->map(function (SaleOrderItem $item) use ($reservations) {
            return [
                'sale_order_item_id' => $item->id,
                'product'            => $item->product,
                'quantity'           => (float) $item->quantity,
                'quantity_picked'    => (float) $item->quantity_picked,
                'is_complete'        => (float) $item->quantity_picked >= (float) $item->quantity,
                'batch_allocations'  => ($reservations[$item->id] ?? collect())->values(),
            ];
        })->all();
    }

    /**
     * @param array<int, array{sale_order_item_id: int, quantity_picked: float}> $items
     */
    public function confirmPicking(SaleOrder $order, array $items, int $userId): SaleOrder
    {
        if ($order->status !== 'separacao') {
            throw StockException::invalidMovement('Picking disponível apenas para pedidos em separação.');
        }

        return DB::transaction(function () use ($order, $items, $userId) {
            foreach ($items as $line) {
                $item = SaleOrderItem::where('sale_order_id', $order->id)
                    ->findOrFail($line['sale_order_item_id']);

                $qty = (float) $line['quantity_picked'];
                if ($qty < 0 || $qty > (float) $item->quantity) {
                    throw StockException::invalidMovement('Quantidade separada inválida.');
                }

                $item->update([
                    'quantity_picked' => $qty,
                    'picked_at'       => $qty > 0 ? now() : null,
                    'picked_by'       => $qty > 0 ? $userId : null,
                ]);
            }

            return $order->fresh(['items.product']);
        });
    }

    public function isFullyPicked(SaleOrder $order): bool
    {
        return $order->items()->get()->every(
            fn (SaleOrderItem $i) => (float) $i->quantity_picked >= (float) $i->quantity
        );
    }
}
