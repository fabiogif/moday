<?php

namespace App\Events;

use App\Models\SaleOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SaleOrder $saleOrder;

    public function __construct(SaleOrder $saleOrder)
    {
        $this->saleOrder = $saleOrder->loadMissing(['client', 'items.product']);
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->saleOrder->tenant_id . '.orders'),
        ];
    }

    /**
     * Reutiliza o mesmo nome do PDV para o painel escutar um único evento.
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    public function broadcastWith(): array
    {
        $clientName = $this->saleOrder->client?->trade_name
            ?? $this->saleOrder->client?->company_name
            ?? $this->saleOrder->client?->name
            ?? $this->saleOrder->client?->contact_name
            ?? 'Cliente';

        return [
            'order' => [
                'id' => $this->saleOrder->id,
                'identify' => $this->saleOrder->identify,
                'customer_name' => $clientName,
                'total' => (float) $this->saleOrder->total,
                'created_at' => optional($this->saleOrder->created_at)->toISOString(),
                'status' => $this->saleOrder->status,
                'source' => 'sale_order',
                'href' => '/sale-orders/' . $this->saleOrder->id,
                'client' => [
                    'name' => $clientName,
                ],
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
