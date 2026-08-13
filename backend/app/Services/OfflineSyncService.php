<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Services\Sale\SaleOrderService;
use Illuminate\Support\Facades\Log;

class OfflineSyncService
{
    public function __construct(
        private readonly SaleOrderService $saleOrderService,
    ) {}

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
                $paymentMethodName = PaymentMethod::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $orderData['payment_method_id'])
                    ->value('name');

                $items = collect($orderData['products'] ?? [])->map(fn (array $item) => [
                    'product_id'       => $item['product_id'],
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $item['price'],
                    'discount_percent' => 0,
                ])->all();

                $validated = [
                    'client_id'         => $orderData['client_id'],
                    'status'            => 'aprovado',
                    'payment_method'    => $paymentMethodName,
                    'payment_term_days' => $orderData['payment_term_days'] ?? 0,
                    'notes'             => $orderData['notes'] ?? null,
                    'freight_amount'    => 0,
                    'discount_amount'   => 0,
                ];

                $saleOrder = $this->saleOrderService->create($tenantId, $userId, $validated, $items);

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
                Log::error('OfflineSync: failed', [
                    'offline_id' => $offlineId,
                    'error'      => $e->getMessage(),
                ]);

                $results[] = [
                    'offline_id' => $offlineId,
                    'success'    => false,
                    'error'      => $e->getMessage() ?: 'Erro ao processar pedido',
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
