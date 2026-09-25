<?php

namespace App\Repositories;

use App\DTO\Integrations\Ifood\IfoodOrderDTO;
use App\Models\Integrations\Ifood\IfoodOrder;
use App\Models\Integrations\Ifood\IfoodOrderItem;
use App\Models\Integrations\Ifood\IfoodOrderStatusLog;
use App\Repositories\Contracts\IfoodOrderRepositoryInterface;
use App\Repositories\Contracts\Presenter\PaginatePresenter;
use Illuminate\Support\Facades\DB;

class IfoodOrderRepository implements IfoodOrderRepositoryInterface
{
    public function __construct(
        private readonly IfoodOrder $order = new IfoodOrder(),
        private readonly IfoodOrderItem $orderItem = new IfoodOrderItem(),
        private readonly IfoodOrderStatusLog $statusLog = new IfoodOrderStatusLog()
    ) {
    }

    public function findByExternalId(string $externalId): ?IfoodOrder
    {
        return $this->order->newQuery()
            ->where('external_order_id', $externalId)
            ->first();
    }

    public function createOrUpdateFromDto(IfoodOrderDTO $dto, ?int $tenantId = null): IfoodOrder
    {
        $tenant = $tenantId;

        if (!$tenant) {
            throw new \InvalidArgumentException('TenantId é obrigatório para sincronizar pedido iFood.');
        }

        return DB::transaction(function () use ($dto, $tenant) {
            $record = $this->order->newQuery()
                ->firstOrNew(['external_order_id' => $dto->externalOrderId]);

            $record->fill([
                'tenant_id' => $tenant,
                'display_id' => $dto->displayId,
                'status' => $dto->statusHistory[0]['state'] ?? 'Recebido do iFood',
                'order_type' => $dto->orderType,
                'order_timing' => $dto->orderTiming,
                'sales_channel' => $dto->salesChannel,
                'category' => $dto->category,
                'total_amount' => $dto->totalAmount,
                'items_total' => $dto->itemsTotal,
                'delivery_fee' => $dto->deliveryFee,
                'benefits_total' => $dto->benefitsTotal,
                'additional_fees_total' => $dto->additionalFeesTotal,
                'customer_name' => $dto->getCustomerName(),
                'customer_phone' => $dto->getCustomerPhone(),
                'customer_document_number' => $dto->getCustomerDocumentNumber(),
                'customer_orders_count' => $dto->getCustomerOrdersCount(),
                'customer_segmentation' => $dto->getCustomerSegmentation(),
                'delivery_address' => $dto->getDeliveryAddress(),
                'preparation_start_at' => $dto->preparationStart,
                'is_test' => $dto->isTest,
                'extra_info' => $dto->extraInfo,
                'merchant_identifier' => $dto->merchant['id'] ?? null,
                'merchant_name' => $dto->merchant['name'] ?? null,
                'benefits' => $dto->benefits,
                'additional_fees' => $dto->additionalFees,
                'payments' => $dto->payments,
                'picking' => $dto->picking,
                'delivery' => $dto->delivery,
                'takeout' => $dto->takeout,
                'dinein' => $dto->dinein,
                'schedule' => $dto->schedule,
                'additional_info' => $dto->additionalInfo,
                'raw_payload' => $dto->rawPayload,
                'received_at' => $record->received_at ?? $dto->createdAt ?? now(),
                'last_synced_at' => now(),
            ]);

            $record->save();

            return $record->fresh(['items', 'statusLogs']);
        });
    }

    public function attachItems(IfoodOrder $order, array $items): void
    {
        DB::transaction(function () use ($order, $items) {
            $order->items()->delete();

            $payload = array_map(function (array $item) {
                return [
                    'ifood_order_id' => $item['ifood_order_id'],
                    'sequence' => $item['sequence'] ?? null,
                    'external_item_id' => $item['external_item_id'] ?? null,
                    'unique_id' => $item['unique_id'] ?? null,
                    'external_code' => $item['external_code'] ?? null,
                    'sku' => $item['sku'] ?? null,
                    'ean' => $item['ean'] ?? null,
                    'name' => $item['name'] ?? null,
                    'quantity' => (int) round($item['quantity'] ?? 0),
                    'quantity_value' => $item['quantity_value'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'price' => $item['price'] ?? 0,
                    'total_price' => $item['total_price'] ?? 0,
                    'options_price' => $item['options_price'] ?? null,
                    'customization_price' => $item['customization_price'] ?? null,
                    'observations' => $item['observations'] ?? null,
                    'options' => !empty($item['options']) ? json_encode($item['options']) : null,
                    'scale_prices' => !empty($item['scale_prices']) ? json_encode($item['scale_prices']) : null,
                    'metadata' => json_encode($item['metadata'] ?? []),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, array_map(function ($item) use ($order) {
                return array_merge($item, ['ifood_order_id' => $order->id]);
            }, $items));

            $this->orderItem->newQuery()->insert($payload);
        });
    }

    public function attachStatus(IfoodOrder $order, array $statusLog): void
    {
        $this->statusLog->newQuery()->create([
            'ifood_order_id' => $order->id,
            'status' => $statusLog['status'] ?? 'Recebido do iFood',
            'sent_to_ifood_at' => $statusLog['sent_to_ifood_at'] ?? null,
            'response_code' => $statusLog['response_code'] ?? null,
            'response_body' => $statusLog['response_body'] ?? null,
            'error_message' => $statusLog['error_message'] ?? null,
        ]);
    }

    public function linkInternalOrder(IfoodOrder $ifoodOrder, int $orderId): void
    {
        $ifoodOrder->update([
            'order_id' => $orderId,
        ]);
    }

    public function paginateByTenant(int $tenantId, int $perPage = 20): \App\Repositories\Contracts\PaginateRepositoryInterface
    {
        $result = $this->order->newQuery()
            ->with(['items', 'order', 'statusLogs'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return new PaginatePresenter($result, ['items', 'order', 'statusLogs']);
    }
}

