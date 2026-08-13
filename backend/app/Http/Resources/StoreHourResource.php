<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreHourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'is_always_open' => $this->is_always_open,
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->day_name,
            'day_name_short' => $this->day_name_short,
            'delivery_type' => $this->delivery_type,
            'delivery_type_label' => $this->delivery_type_label,
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'start_time_2' => $this->start_time_2?->format('H:i'),
            'end_time_2' => $this->end_time_2?->format('H:i'),
            'start_time_formatted' => $this->start_time?->format('H:i'),
            'end_time_formatted' => $this->end_time?->format('H:i'),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

