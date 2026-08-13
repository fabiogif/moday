<?php

namespace App\Services\Purchase;

use App\Models\AccountPayable;
use App\Models\PurchaseOrder;
use Carbon\Carbon;

class PurchaseFinancialService
{
    public function createPayableOnReceive(PurchaseOrder $order, float $receivedValue): ?AccountPayable
    {
        if ($receivedValue <= 0) {
            return null;
        }

        $existing = AccountPayable::where('purchase_order_id', $order->id)
            ->where('status', '!=', 'cancelado')
            ->first();

        if ($existing) {
            $existing->update([
                'amount' => (float) $existing->amount + $receivedValue,
                'notes'  => trim(($existing->notes ?? '') . "\nRecebimento adicional: R$ " . number_format($receivedValue, 2, ',', '.')),
            ]);
            return $existing->fresh();
        }

        $dueDate = Carbon::today()->addDays((int) ($order->payment_term_days ?? 30));

        return AccountPayable::create([
            'tenant_id'          => $order->tenant_id,
            'supplier_id'        => $order->supplier_id,
            'purchase_order_id'  => $order->id,
            'description'        => "Pedido de compra {$order->identify}",
            'issue_date'         => Carbon::today(),
            'due_date'           => $dueDate,
            'amount'             => $receivedValue,
            'amount_paid'        => null,
            'status'             => 'pendente',
            'document_number'    => $order->identify,
            'notes'              => $order->notes,
        ]);
    }
}
