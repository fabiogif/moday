<?php

namespace App\Services\Sale;

use App\Models\AccountReceivable;
use App\Models\SaleOrder;
use Carbon\Carbon;

class SaleOrderFinancialService
{
    public function createReceivableOnBilling(SaleOrder $order): ?AccountReceivable
    {
        if ($order->total <= 0) {
            return null;
        }

        $existing = AccountReceivable::where('sale_order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $dueDate = $order->due_date
            ?? Carbon::today()->addDays((int) ($order->payment_term_days ?? 30));

        return AccountReceivable::create([
            'tenant_id'       => $order->tenant_id,
            'client_id'       => $order->client_id,
            'sale_order_id'   => $order->id,
            'description'     => "Pedido de venda {$order->identify}",
            'issue_date'      => Carbon::today(),
            'due_date'        => $dueDate,
            'amount'          => $order->total,
            'amount_received' => null,
            'status'          => 'pendente',
            'document_number' => $order->identify,
            'notes'           => $order->notes,
        ]);
    }

    public function reversePartialReceivable(SaleOrder $order, float $amount): void
    {
        $ar = AccountReceivable::where('sale_order_id', $order->id)->first();
        if (!$ar) {
            return;
        }

        $newAmount = max(0, (float) $ar->amount - $amount);
        $ar->update([
            'amount' => $newAmount,
            'status' => $newAmount <= 0 ? 'cancelado' : $ar->status,
            'notes'  => trim(($ar->notes ?? '') . "\nEstorno devolução: R$ " . number_format($amount, 2, ',', '.')),
        ]);
    }
}
