<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveOldOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoffDate = now()->subYear();

        Log::info('ArchiveOldOrders job iniciado', [
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);

        $ordersToArchive = Order::whereNull('archived_at')
            ->where('status', '!=', Order::STATUS_ARCHIVED)
            ->where('created_at', '<=', $cutoffDate)
            ->get(['id', 'identify', 'tenant_id']);

        if ($ordersToArchive->isEmpty()) {
            Log::info('ArchiveOldOrders job concluído: nenhum pedido elegível para arquivamento.');
            return;
        }

        $orderIds = $ordersToArchive->pluck('id');

        Order::whereIn('id', $orderIds)->update([
            'status' => Order::STATUS_ARCHIVED,
            'archived_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('ArchiveOldOrders job concluído: pedidos arquivados.', [
            'total_archived' => $ordersToArchive->count(),
            'orders' => $ordersToArchive->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'identify' => $order->identify,
                    'tenant_id' => $order->tenant_id,
                ];
            })->toArray(),
        ]);
    }
}


