<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identify' => $this->uuid,
            'name' => $this->name,
            'url' => $this->image ? url("storage/{$this->image}") : null,
            'description' => $this->description,
            'price' => $this->price,
            'price_cost' => $this->price_cost,
            'promotional_price' => $this->promotional_price,
            'brand' => $this->brand,
            'sku' => $this->sku,
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'depth' => $this->depth,
            'shipping_info' => $this->shipping_info,
            'warehouse_location' => $this->warehouse_location,
            'variations' => $this->variations,
            'qtd_stock' => $this->qtd_stock,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('d/m/Y') : null,
            'categories' => CategoryResource::collection($this->categories ?? collect())
        ];
    }

}
