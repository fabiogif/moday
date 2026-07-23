<?php

namespace App\Http\Resources\Visit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'type' => $this->type,
            'priority' => $this->priority,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'scheduled_start_time' => $this->scheduled_start_time,
            'scheduled_end_time' => $this->scheduled_end_time,
            'objective_notes' => $this->objective_notes,
            'notes' => $this->notes,

            'checkin_at' => $this->checkin_at?->toIso8601String(),
            'checkin_lat' => $this->checkin_lat !== null ? (float) $this->checkin_lat : null,
            'checkin_lng' => $this->checkin_lng !== null ? (float) $this->checkin_lng : null,
            'checkin_address' => $this->checkin_address,
            'checkin_distance_meters' => $this->checkin_distance_meters,
            'checkin_out_of_range' => (bool) $this->checkin_out_of_range,

            'checkout_at' => $this->checkout_at?->toIso8601String(),
            'checkout_address' => $this->checkout_address,
            'service_duration_minutes' => $this->service_duration_minutes,
            'result' => $this->result,
            'order_value' => $this->order_value !== null ? (float) $this->order_value : null,
            'has_pending_issue' => (bool) $this->has_pending_issue,
            'next_visit_suggested_at' => $this->next_visit_suggested_at?->format('Y-m-d'),

            'rescheduled_from_visit_id' => $this->rescheduled_from_visit_id,

            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'uuid' => $this->client->uuid,
                'name' => $this->client->company_name ?: $this->client->name,
                'cnpj' => $this->client->cnpj,
                'contact_name' => $this->client->contact_name,
                'phone' => $this->client->phone,
                'whatsapp' => $this->client->whatsapp,
                'address' => $this->client->full_address,
                'city' => $this->client->city,
                'neighborhood' => $this->client->neighborhood,
                'latitude' => $this->client->latitude !== null ? (float) $this->client->latitude : null,
                'longitude' => $this->client->longitude !== null ? (float) $this->client->longitude : null,
                'is_vip' => (bool) $this->client->is_vip,
                'abc_classification' => $this->client->abc_classification,
            ]),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),

            'client_alert' => $this->when(isset($this->client_alert), fn () => $this->client_alert),

            'status_histories' => VisitStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'media' => VisitMediaResource::collection($this->whenLoaded('media')),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
