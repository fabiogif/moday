<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'points_per_currency' => (float) $this->points_per_currency,
            'min_purchase_amount' => $this->min_purchase_amount ? (float) $this->min_purchase_amount : null,
            'max_points_per_purchase' => $this->max_points_per_purchase ? (float) $this->max_points_per_purchase : null,
            'points_expiry_days' => $this->points_expiry_days,
            'excluded_categories' => $this->excluded_categories ?? [],
            'excluded_products' => $this->excluded_products ?? [],
            'birthday_multiplier' => $this->birthday_multiplier ? (float) $this->birthday_multiplier : null,
            'special_day_multipliers' => $this->special_day_multipliers ?? [],
            'rewards' => LoyaltyRewardResource::collection($this->whenLoaded('rewards')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

