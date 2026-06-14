<?php

namespace App\Http\Resources\Integrations\Ifood;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IfoodOrderResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->external_order_id,
            'display_id' => $this->display_id ?? data_get($this->raw_payload, 'displayId'),
            'status' => $this->status,
            'order_type' => $this->order_type,
            'order_timing' => $this->order_timing,
            'sales_channel' => $this->sales_channel,
            'category' => $this->category,
            'is_test' => (bool) $this->is_test,
            'extra_info' => $this->extra_info,
            'merchant' => [
                'id' => $this->merchant_identifier,
                'name' => $this->merchant_name,
            ],
            'customer' => [
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
                'document_number' => $this->customer_document_number,
                'orders_count_on_merchant' => $this->customer_orders_count,
                'segmentation' => $this->customer_segmentation,
            ],
            'totals' => [
                'order_amount' => $this->total_amount !== null ? (float) $this->total_amount : null,
                'items_total' => $this->items_total !== null ? (float) $this->items_total : null,
                'delivery_fee' => $this->delivery_fee !== null ? (float) $this->delivery_fee : null,
                'benefits_total' => $this->benefits_total !== null ? (float) $this->benefits_total : null,
                'additional_fees_total' => $this->additional_fees_total !== null ? (float) $this->additional_fees_total : null,
            ],
            'addresses' => [
                'delivery' => $this->delivery_address,
            ],
            'timestamps' => [
                'received_at' => optional($this->received_at)->toIso8601String(),
                'preparation_start_at' => optional($this->preparation_start_at)->toIso8601String(),
                'last_synced_at' => optional($this->last_synced_at)->toIso8601String(),
                'created_at' => optional($this->created_at)->toIso8601String(),
                'updated_at' => optional($this->updated_at)->toIso8601String(),
            ],
            'benefits' => [
                'items' => $this->benefits ?? [],
                'total' => $this->benefits_total !== null ? (float) $this->benefits_total : null,
            ],
            'additional_fees' => [
                'items' => $this->additional_fees ?? [],
                'total' => $this->additional_fees_total !== null ? (float) $this->additional_fees_total : null,
            ],
            'payments' => $this->payments ?? [],
            'picking' => $this->picking ?? [],
            'delivery' => $this->delivery ?? [],
            'takeout' => $this->takeout ?? [],
            'dinein' => $this->dinein ?? [],
            'schedule' => $this->schedule ?? [],
            'additional_info' => $this->additional_info ?? [],
            'items' => IfoodOrderItemResource::collection($this->whenLoaded('items')),
            'status_logs' => IfoodOrderStatusLogResource::collection($this->whenLoaded('statusLogs')),
            'internal_order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order?->id,
                    'identify' => $this->order?->identify,
                    'status' => $this->order?->status,
                    'total' => $this->order?->total,
                ];
            }),
            'raw_payload' => $this->raw_payload,
        ];
    }
}

