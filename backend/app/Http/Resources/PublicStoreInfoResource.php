<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicStoreInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'tenant_id' => $this->resource['id'] ?? null, // Alias para compatibilidade
            'uuid' => $this->resource['uuid'] ?? null,
            'name' => $this->resource['name'] ?? '',
            'slug' => $this->resource['slug'] ?? '',
            'email' => $this->resource['email'] ?? null,
            'phone' => $this->resource['phone'] ?? null,
            'address' => $this->resource['address'] ?? null,
            'city' => $this->resource['city'] ?? null,
            'state' => $this->resource['state'] ?? null,
            'zipcode' => $this->resource['zipcode'] ?? null,
            'logo' => $this->resource['logo'] ?? null,
            'whatsapp' => $this->resource['whatsapp'] ?? null,
            'settings' => $this->resource['settings'] ?? [],
        ];
    }
}

