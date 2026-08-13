<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyRewardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'loyalty_program_id' => $this->loyalty_program_id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'points_required' => $this->points_required,
            'discount_value' => $this->discount_value ? (float) $this->discount_value : null,
            'product' => $this->whenLoaded('product', fn() => [
                'id' => $this->product->id,
                'uuid' => $this->product->uuid,
                'name' => $this->product->name,
                'price' => (float) $this->product->price,
            ]),
            'stock_quantity' => $this->stock_quantity,
            'max_redemptions_per_user' => $this->max_redemptions_per_user,
            'validity_days' => $this->validity_days,
            'is_active' => $this->is_active,
            'available_from' => $this->available_from?->format('Y-m-d'),
            'available_until' => $this->available_until?->format('Y-m-d'),
            'is_available' => $this->isAvailable(),
            'has_stock' => $this->hasStock(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

