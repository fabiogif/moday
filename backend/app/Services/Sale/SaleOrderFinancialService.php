<?php

namespace App\Services\Sale;

use App\Models\AccountReceivable;
use App\Models\SaleOrder;
use Carbon\Carbon;

class SaleOrderFinancialService
{
    /**
     * @return AccountReceivable|AccountReceivable[]|null
     */
    public function createReceivableOnBilling(SaleOrder $order): mixed
    {
        if ($order->total <= 0) {
            return null;
        }

        $existing = AccountReceivable::where('sale_order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $installments = (int) ($order->installments ?? 1);

        if ($order->payment_method === 'dinheiro_parcelado' && $installments > 1) {
            return $this->createInstallmentReceivables($order, $installments);
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

    /** @return AccountReceivable[] */
    private function createInstallmentReceivables(SaleOrder $order, int $installments): array
    {
        $total = (float) $order->total;
        $baseAmount = round($total / $installments, 2);
        $remainder = round($total - ($baseAmount * $installments), 2);
        $intervalDays = max((int) ($order->payment_term_days ?? 30), 1);
        $today = Carbon::today();
        $receivables = [];

        for ($i = 1; $i <= $installments; $i++) {
            $amount = $baseAmount;
            if ($i === $installments) {
                $amount = round($amount + $remainder, 2);
            }

            $receivables[] = AccountReceivable::create([
                'tenant_id'       => $order->tenant_id,
                'client_id'       => $order->client_id,
                'sale_order_id'   => $order->id,
                'description'     => "Pedido {$order->identify} — parcela {$i}/{$installments}",
                'issue_date'      => $today,
                'due_date'        => $today->copy()->addDays($intervalDays * $i),
                'amount'          => $amount,
                'amount_received' => null,
                'status'          => 'pendente',
                'document_number' => "{$order->identify}-{$i}/{$installments}",
                'notes'           => $order->notes,
            ]);
        }

        return $receivables;
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
