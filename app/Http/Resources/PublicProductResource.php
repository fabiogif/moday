<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProductResource extends JsonResource
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
            'description' => $this->resource['description'] ?? null,
            'price' => $this->resource['price'] ?? 0,
            'promotional_price' => $this->resource['promotional_price'] ?? null,
            'image' => $this->resource['image'] ?? null,
            'qtd_stock' => $this->resource['qtd_stock'] ?? 0,
            'brand' => $this->resource['brand'] ?? null,
            'sku' => $this->resource['sku'] ?? null,
            'variations' => $this->resource['variations'] ?? [],
            'optionals' => $this->resource['optionals'] ?? [],
            'categories' => $this->resource['categories'] ?? [],
        ];
    }
}

