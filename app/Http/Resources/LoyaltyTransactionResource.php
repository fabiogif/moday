<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'points' => $this->points,
            'balance_after' => $this->balance_after,
            'order_id' => $this->order_id,
            'purchase_amount' => $this->purchase_amount ? (float) $this->purchase_amount : null,
            'multiplier' => (float) $this->multiplier,
            'description' => $this->description,
            'expires_at' => $this->expires_at?->format('Y-m-d'),
            'is_expired' => $this->isExpired(),
            'reward' => $this->whenLoaded('loyaltyReward', fn() => [
                'name' => $this->loyaltyReward->name,
                'type' => $this->loyaltyReward->type_label,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

