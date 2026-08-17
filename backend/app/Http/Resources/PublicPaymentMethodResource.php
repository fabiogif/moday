<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPaymentMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource['uuid'] ?? null,
            'name' => $this->resource['name'] ?? '',
            'type' => $this->resource['type'] ?? null,
            'description' => $this->resource['description'] ?? null,
            'is_active' => $this->resource['is_active'] ?? true,
        ];
    }
}

