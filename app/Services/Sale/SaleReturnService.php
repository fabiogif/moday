<?php

namespace App\Services\Sale;

use App\Exceptions\StockException;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\StockReservation;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\DB;

class SaleReturnService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly SaleOrderFinancialService $financialService,
    ) {}

    /**
     * @param array<int, array{sale_order_item_id: int, quantity: float, warehouse_id: int}> $items
     */
    public function processReturn(SaleOrder $order, array $items, int $userId): SaleOrder
    {
        if (!in_array($order->status, ['faturado', 'entregue'])) {
            throw StockException::invalidMovement('Devolução permitida apenas para pedidos faturados ou entregues.');
        }

        return DB::transaction(function () use ($order, $items, $userId) {
            $returnTotal = 0;

            foreach ($items as $returnItem) {
                $line = SaleOrderItem::where('sale_order_id', $order->id)
                    ->findOrFail($returnItem['sale_order_item_id']);

                $qty = (float) $returnItem['quantity'];
                if ($qty <= 0 || $qty > (float) $line->quantity) {
                    throw StockException::invalidMovement('Quantidade de devolução inválida.');
                }

                if (($line->item_type ?? 'venda') === 'bonificacao') {
                    continue;
                }

                $this->stockService->recordReturn(
                    $order->tenant_id,
                    $line->product_id,
                    (int) $returnItem['warehouse_id'],
                    $qty,
                    $userId,
                    [
                        'batch_number'   => 'DEV-' . $order->identify . '-' . $line->id,
                        'reference_type' => 'sale_order',
                        'reference_id'   => $order->id,
                        'unit_cost'      => $line->unit_price,
                        'notes'          => "Devolução pedido {$order->identify}",
                    ],
                );

                $lineValue = $qty * (float) $line->unit_price * (1 - (float) $line->discount_percent / 100);
                $returnTotal += $lineValue;
            }

            if ($returnTotal > 0) {
                $this->financialService->reversePartialReceivable($order, $returnTotal);
            }

            $order->update(['notes' => trim(($order->notes ?? '') . "\nDevolução registrada em " . now()->format('d/m/Y H:i'))]);

            return $order->fresh(['items.product']);
        });
    }
}
