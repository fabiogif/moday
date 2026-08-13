<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesGoalProgressLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'sales_goal_id' => $this->sales_goal_id,
            'sale_order_id' => $this->sale_order_id,
            'previous_value' => (float) $this->previous_value,
            'added_value' => (float) $this->added_value,
            'new_value' => (float) $this->new_value,
            'event_type' => $this->event_type,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
