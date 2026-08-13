<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            
            // Dados da avaliação
            'rating' => $this->rating,
            'comment' => $this->comment,
            'customer_name' => $this->customer_name ?? $this->user?->name ?? 'Cliente',
            'customer_email' => $this->when(
                $request->user() && $request->user()->tenant_id === $this->tenant_id,
                $this->customer_email ?? $this->user?->email
            ),
            
            // Status
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'helpful_count' => $this->helpful_count,
            
            // Relacionamentos
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order?->id,
                    'identify' => $this->order?->identify,
                    'total' => $this->order?->total,
                    'created_at' => $this->order?->created_at?->format('d/m/Y H:i'),
                ];
            }),
            
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ] : null;
            }),
            
            // Moderação (apenas para admin)
            'moderator' => $this->when(
                $this->moderated_by && $request->user(),
                function () {
                    return [
                        'id' => $this->moderator?->id,
                        'name' => $this->moderator?->name,
                        'moderated_at' => $this->moderated_at?->format('d/m/Y H:i'),
                    ];
                }
            ),
            'moderation_reason' => $this->when(
                $this->moderation_reason && $request->user(),
                $this->moderation_reason
            ),
            
            // Timestamps
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}

