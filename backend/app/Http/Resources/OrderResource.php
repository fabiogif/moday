<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Verificar se o relacionamento client foi carregado
        $clientData = null;
        if ($this->relationLoaded('client') && $this->client) {
            $clientData = new ClientResource($this->client);
        }

        return [
            'id' => $this->id, // ID numérico do pedido (para reviews)
            'order_id' => $this->identify, // Identificador legível (ex: "ABC123")
            'identify' => $this->identify,
            'total' => $this->total,
            'client' => $clientData,
            'client_full_name' => $this->client?->name,
            'client_email' => $this->client?->email,
            'client_phone' => $this->client?->phone,   

            'table' => $this->whenLoaded('table', function () {
                return $this->table ? new TableResource($this->table) : null;
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return $this->tenant ? new TenantResource($this->tenant) : null;
            }),
            'date' => $this->created_at ? Carbon::parse($this->created_at)->format(format: 'd/m/Y H:i:s') : null,
            'status' => $this->status,
            'status_position' => $this->orderStatus?->order_position,
            'status_uuid' => $this->orderStatus?->uuid,
            'status_color' => $this->orderStatus?->color,
            'status_icon' => $this->orderStatus?->icon,
            'order_status_id' => $this->order_status_id,
            'comment' => $this->comment,
            'products' => ProductResource::collection($this->whenLoaded('products', $this->products ?? collect())),
            'evaluations' => EvaluationResource::collection($this->whenLoaded('evaluations', $this->evaluations ?? collect())),
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format(format: 'd/m/Y H:i:s') : null,
            'archived_at' => $this->archived_at ? $this->archived_at->toIso8601String() : null,
            // Delivery fields
            'is_delivery' => $this->is_delivery,
            'use_client_address' => $this->use_client_address,
            'delivery_address' => $this->delivery_address,
            'delivery_city' => $this->delivery_city,
            'delivery_state' => $this->delivery_state,
            'delivery_zip_code' => $this->delivery_zip_code,
            'delivery_neighborhood' => $this->delivery_neighborhood,
            'delivery_number' => $this->delivery_number,
            'delivery_complement' => $this->delivery_complement,
            'delivery_notes' => $this->delivery_notes,
            'full_delivery_address' => $this->full_delivery_address, // Now works directly
            'payment_method' => $this->payment_method,
            'payment_method_name' => $this->whenLoaded('paymentMethod', fn () => $this->paymentMethod?->name)
                ?? $this->payment_method,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('d/m/Y H:i:s') : null,
        ];
    }
}
