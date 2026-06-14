<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyRedemptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reward' => $this->whenLoaded('loyaltyReward', fn() => [
                'name' => $this->loyaltyReward->name,
                'type' => $this->loyaltyReward->type_label,
                'discount_value' => $this->loyaltyReward->discount_value,
            ]),
            'points_used' => $this->points_used,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'coupon_code' => $this->coupon_code,
            'redeemed_at' => $this->redeemed_at?->format('d/m/Y'),
            'expires_at' => $this->expires_at?->format('d/m/Y'),
            'used_at' => $this->used_at?->format('d/m/Y'),
            'is_expired' => $this->isExpired(),
            'order_id' => $this->order_id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

