<?php

namespace App\Services\Commercial;

use App\Exceptions\CreditException;
use App\Models\AccountReceivable;
use App\Models\Client;
use App\Models\SaleOrder;

class CreditLimitService
{
    public function validateForSaleOrder(SaleOrder $order): void
    {
        if (!$order->client_id) {
            return;
        }

        $client = Client::find($order->client_id);
        if (!$client) {
            return;
        }

        if ($client->is_blocked) {
            throw CreditException::clientBlocked($client->blocked_reason ?? '');
        }

        $limit = (float) ($client->credit_limit ?? 0);
        if ($limit <= 0) {
            return;
        }

        $openBalance = $this->getOpenReceivableBalance($client->id);
        $orderTotal  = (float) $order->total;

        if ($openBalance + $orderTotal > $limit + 0.01) {
            throw CreditException::limitExceeded($limit, $openBalance, $orderTotal);
        }
    }

    public function getOpenReceivableBalance(int $clientId): float
    {
        return (float) AccountReceivable::where('client_id', $clientId)
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->selectRaw('COALESCE(SUM(amount - COALESCE(amount_received, 0)), 0) as balance')
            ->value('balance');
    }
}
