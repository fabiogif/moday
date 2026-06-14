<?php

namespace App\Services;

use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncService
{
    /**
     * Persists a batch of offline-queued B2B sale orders.
     * Returns per-item results so the client can purge synced entries from its queue.
     */
    public function syncOrders(int $tenantId, int $userId, array $orders): array
    {
        $results = [];

        foreach ($orders as $orderData) {
            $offlineId = $orderData['offline_id'];

            try {
                DB::beginTransaction();

                $saleOrder = SaleOrder::create([
                    'tenant_id'         => $tenantId,
                    'user_id'           => $userId,
                    'client_id'         => $orderData['client_id'],
                    'payment_method_id' => $orderData['payment_method_id'],
                    'status'            => 'aprovado',
                    'total'             => $orderData['total'],
                    'notes'             => $orderData['notes'] ?? null,
                    'is_delivery'       => false,
                ]);

                foreach ($orderData['products'] as $item) {
                    SaleOrderItem::create([
                        'sale_order_id' => $saleOrder->id,
                        'product_id'    => $item['product_id'],
                        'item_type'     => 'venda',
                        'quantity'      => $item['quantity'],
                        'unit_price'    => $item['price'],
                        'subtotal'      => $item['price'] * $item['quantity'],
                    ]);
                }

                DB::commit();

                Log::info('OfflineSync: sale order created', [
                    'offline_id' => $offlineId,
                    'order_id'   => $saleOrder->id,
                ]);

                $results[] = [
                    'offline_id' => $offlineId,
                    'success'    => true,
                    'order_id'   => $saleOrder->id,
                    'identify'   => $saleOrder->identify,
                ];
            } catch (\Throwable $e) {
                DB::rollBack();

                Log::error('OfflineSync: failed', [
                    'offline_id' => $offlineId,
                    'error'      => $e->getMessage(),
                ]);

                $results[] = [
                    'offline_id' => $offlineId,
                    'success'    => false,
                    'error'      => 'Erro ao processar pedido',
                ];
            }
        }

        return $results;
    }

    public function countSynced(array $results): int
    {
        return collect($results)->where('success', true)->count();
    }
}
