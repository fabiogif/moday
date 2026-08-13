<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'color' => $this->color ?? '#3b82f6',
            'start_date' => $this->start_date?->toISOString(),
            'start_date_formatted' => $this->start_date?->format('d/m/Y H:i'),
            'end_date' => $this->end_date?->toISOString(),
            'end_date_formatted' => $this->end_date?->format('d/m/Y H:i'),
            'duration_minutes' => $this->duration_minutes,
            'location' => $this->location,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'notifications_sent' => $this->notifications_sent,
            'clients' => ClientResource::collection($this->whenLoaded('clients')),
            'clients_count' => $this->whenLoaded('clients', fn() => $this->clients->count(), 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get translated type label
     */
    private function getTypeLabel(): string
    {
        return match($this->type) {
            'promocao' => 'Promoção',
            'aviso' => 'Aviso',
            'outro' => 'Outro',
            default => 'Outro',
        };
    }
}

