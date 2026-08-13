<?php

namespace App\Http\Resources\Visit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitRecurrenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'frequency' => $this->frequency,
            'interval_count' => $this->interval_count,
            'days_of_week' => $this->days_of_week,
            'day_of_month' => $this->day_of_month,
            'scheduled_start_time' => $this->scheduled_start_time,
            'scheduled_end_time' => $this->scheduled_end_time,
            'type' => $this->type,
            'priority' => $this->priority,
            'starts_on' => $this->starts_on?->format('Y-m-d'),
            'ends_on' => $this->ends_on?->format('Y-m-d'),
            'is_active' => (bool) $this->is_active,

            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'uuid' => $this->client->uuid,
                'name' => $this->client->company_name ?: $this->client->name,
            ]),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
