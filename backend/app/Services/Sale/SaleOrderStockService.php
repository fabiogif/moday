<?php

namespace App\Services\Sale;

use App\Exceptions\StockException;
use App\Models\Batch;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\StockReservation;
use App\Services\Regulatory\RegulatoryValidationService;
use App\Services\Stock\FefoAllocationService;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\DB;

class SaleOrderStockService
{
    public function __construct(
        private readonly FefoAllocationService $fefoAllocationService,
        private readonly StockService $stockService,
        private readonly RegulatoryValidationService $regulatoryValidationService,
    ) {}

    public function validateItemsStock(int $tenantId, array $items, int $userId): void
    {
        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity  = (float) $item['quantity'];

            $this->stockService->ensureStockFromDeclaredQuantity($tenantId, $productId, $userId);

            $available = $this->stockService->getAvailableQuantity($tenantId, $productId);

            if ($quantity > $available) {
                $product = \App\Models\Product::find($productId);
                throw StockException::insufficientStock(
                    $product?->name ?? "produto #{$productId}",
                    $quantity,
                    $available
                );
            }
        }
    }

    public function reserveForOrder(SaleOrder $order, int $userId): void
    {
        DB::transaction(function () use ($order, $userId) {
            $order->load('items.product');
            $this->regulatoryValidationService->validateSaleOrder($order);

            foreach ($order->items as $item) {
                $this->reserveItem($order, $item);
            }

            $this->regulatoryValidationService->validateReservedItems($order->fresh(['items.product']));
        });
    }

    public function fulfillForOrder(SaleOrder $order, int $userId): void
    {
        DB::transaction(function () use ($order, $userId) {
            $reservations = StockReservation::where('sale_order_id', $order->id)
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $batch = Batch::lockForUpdate()->findOrFail($reservation->batch_id);

                $batch->quantity_reserved = max(0, (float) $batch->quantity_reserved - (float) $reservation->quantity);
                $batch->quantity_available = max(0, (float) $batch->quantity_available - (float) $reservation->quantity);
                $batch->quantity_sold      = (float) $batch->quantity_sold + (float) $reservation->quantity;
                $batch->save();

                \App\Models\StockMovement::create([
                    'tenant_id'      => $order->tenant_id,
                    'product_id'     => $batch->product_id,
                    'batch_id'       => $batch->id,
                    'warehouse_id'   => $batch->warehouse_id,
                    'performed_by'   => $userId,
                    'type'           => 'saida',
                    'quantity'       => $reservation->quantity,
                    'unit_cost'      => $batch->unit_cost,
                    'reference_type' => 'sale_order',
                    'reference_id'   => $order->id,
                    'notes'          => "Faturamento pedido {$order->identify}",
                ]);

                $reservation->update(['status' => 'fulfilled']);
                $this->stockService->syncProductStock($batch->product_id);
            }
        });
    }

    public function releaseForOrder(SaleOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $reservations = StockReservation::where('sale_order_id', $order->id)
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $batch = Batch::lockForUpdate()->find($reservation->batch_id);
                if ($batch) {
                    $batch->quantity_reserved = max(
                        0,
                        (float) $batch->quantity_reserved - (float) $reservation->quantity
                    );
                    $batch->save();
                    $this->stockService->syncProductStock($batch->product_id);
                }
                $reservation->update(['status' => 'released']);
            }
        });
    }

    private function reserveItem(SaleOrder $order, SaleOrderItem $item): void
    {
        $existing = StockReservation::where('sale_order_item_id', $item->id)
            ->where('status', 'reserved')
            ->exists();

        if ($existing) {
            return;
        }

        $allocations = $this->fefoAllocationService->allocate(
            $order->tenant_id,
            $item->product_id,
            (float) $item->quantity,
        );

        $primaryBatchId = null;

        foreach ($allocations as $allocation) {
            $batch = Batch::lockForUpdate()->findOrFail($allocation['batch_id']);
            $batch->quantity_reserved = (float) $batch->quantity_reserved + $allocation['quantity'];
            $batch->save();

            StockReservation::create([
                'tenant_id'          => $order->tenant_id,
                'sale_order_id'      => $order->id,
                'sale_order_item_id' => $item->id,
                'batch_id'           => $batch->id,
                'quantity'           => $allocation['quantity'],
                'status'             => 'reserved',
            ]);

            $primaryBatchId ??= $batch->id;
        }

        if ($primaryBatchId && !$item->batch_id) {
            $item->update(['batch_id' => $primaryBatchId]);
        }
    }
}
