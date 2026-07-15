<?php

namespace App\Services\Purchase;

use App\Exceptions\StockException;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\DB;

class PurchaseReceiveService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly PurchaseFinancialService $purchaseFinancialService,
    ) {}

    /**
     * @param array<int, array{purchase_order_item_id: int, quantity: float, batch_number?: string, manufacture_date?: string, expiry_date?: string, warehouse_id: int}> $items
     */
    public function receive(PurchaseOrder $order, array $items, int $performedBy): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $items, $performedBy) {
            if (!$order->canReceive()) {
                throw StockException::invalidMovement(
                    'Apenas pedidos confirmados podem ser recebidos.'
                );
            }

            if ($order->supplier_id) {
                $supplier = Supplier::find($order->supplier_id);
                if ($supplier && !$supplier->is_active) {
                    throw StockException::invalidMovement('Fornecedor não está homologado/ativo.');
                }
            }

            $order->load('items.product');

            foreach ($items as $receiveItem) {
                $this->receiveLine($order, $receiveItem, $performedBy);
            }

            $order->load('items');

            $receivedValue = 0;
            foreach ($items as $receiveItem) {
                $line = $order->items->firstWhere('id', $receiveItem['purchase_order_item_id']);
                if ($line) {
                    $receivedValue += (float) $receiveItem['quantity'] * (float) $line->unit_cost;
                }
            }

            if ($receivedValue > 0) {
                $this->purchaseFinancialService->createPayableOnReceive($order, $receivedValue);
            }

            $allReceived = $order->items->every(
                fn (PurchaseOrderItem $i) => (float) $i->quantity_received >= (float) $i->quantity_ordered
            );

            $order->update([
                'status'      => $allReceived ? 'recebido' : 'confirmado',
                'received_at' => $allReceived ? now() : $order->received_at,
            ]);

            return $order->fresh(['items.product', 'supplier']);
        });
    }

    private function receiveLine(PurchaseOrder $order, array $data, int $performedBy): void
    {
        $item = PurchaseOrderItem::where('purchase_order_id', $order->id)
            ->where('id', $data['purchase_order_item_id'])
            ->lockForUpdate()
            ->firstOrFail();

        $qty = (float) $data['quantity'];
        if ($qty <= 0) {
            throw StockException::invalidMovement('Quantidade recebida deve ser maior que zero.');
        }

        $remaining = (float) $item->remaining_to_receive;
        if ($qty > $remaining + 0.0001) {
            throw StockException::invalidMovement(
                "Quantidade recebida ({$qty}) excede o pendente ({$remaining}) do item #{$item->id}."
            );
        }

        $batchNumber     = $data['batch_number'] ?? $item->batch_number ?? 'PC-' . $order->id . '-' . $item->id;
        $manufactureDate = $data['manufacture_date'] ?? null;
        $expiryDate      = $data['expiry_date'] ?? $item->expiry_date?->toDateString();

        $this->stockService->recordEntry(
            tenantId: $order->tenant_id,
            productId: $item->product_id,
            warehouseId: (int) $data['warehouse_id'],
            quantity: $qty,
            performedBy: $performedBy,
            options: [
                'batch_number'      => $batchNumber,
                'manufacture_date'  => $manufactureDate,
                'expiry_date'       => $expiryDate,
                'unit_cost'         => $item->unit_cost,
                'supplier_id'       => $order->supplier_id,
                'purchase_order_id' => $order->id,
                'reference_type'    => 'purchase_order',
                'reference_id'      => $order->id,
                'notes'             => "Recebimento pedido {$order->identify}",
            ],
        );

        $item->quantity_received = (float) $item->quantity_received + $qty;
        if (!empty($data['batch_number'])) {
            $item->batch_number = $data['batch_number'];
        }
        if (!empty($data['expiry_date'])) {
            $item->expiry_date = $data['expiry_date'];
        }
        $item->save();
    }
}
