<?php

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
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
        $imageUrl = ImageHelper::publicAssetPath($this->image, 'products');

        return [
            'id' => $this->id,
            'identify' => $this->uuid,
            'name' => $this->name,
            'url' => $imageUrl,
            'image' => $imageUrl,
            'description' => $this->description,
            'price' => (float) $this->price,
            'price_cost' => (float) $this->price_cost,
            'quantity' => $this->when(
                $this->pivot,
                (int) ($this->pivot->qty ?? $this->pivot->quantity ?? 1)
            ),
            'unit_price' => $this->when(
                $this->pivot,
                (float) ($this->pivot->price ?? $this->price)
            ),
            'total' => $this->when(
                $this->pivot,
                (float) (($this->pivot->qty ?? $this->pivot->quantity ?? 1) * ($this->pivot->price ?? $this->price))
            ),
            'promotional_price' => $this->promotional_price ? (float) $this->promotional_price : null,
            'brand' => $this->brand,
            'sku' => $this->sku,
            'weight' => $this->weight ? (float) $this->weight : null,
            'height' => $this->height ? (float) $this->height : null,
            'width' => $this->width ? (float) $this->width : null,
            'depth' => $this->depth ? (float) $this->depth : null,
            'shipping_info' => $this->shipping_info,
            'warehouse_location' => $this->warehouse_location,
            'variations' => is_array($this->variations) ? $this->variations : (is_string($this->variations) ? json_decode($this->variations, true) : []) ?? [],
            'optionals' => is_array($this->optionals) ? $this->optionals : (is_string($this->optionals) ? json_decode($this->optionals, true) : []) ?? [],
            'qtd_stock' => (int) $this->qtd_stock,
            'sell_without_stock' => (bool) ($this->sell_without_stock ?? false),
            'product_type' => $this->product_type,
            'requires_prescription' => (bool) ($this->requires_prescription ?? false),
            'controlled_substance' => (bool) ($this->controlled_substance ?? false),
            'storage_temperature' => $this->storage_temperature,
            'tax_ncm' => $this->tax_ncm,
            'tax_cst' => $this->tax_cst,
            'tax_rate' => $this->tax_rate ? (float) $this->tax_rate : null,
            'unit_of_measure' => $this->unit_of_measure,
            'units_per_box' => $this->units_per_box,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'reorder_point' => $this->reorder_point,
            'price_minimum' => $this->price_minimum ? (float) $this->price_minimum : null,
            'barcode' => $this->barcode,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('d/m/Y') : null,
            'categories' => CategoryResource::collection($this->categories ?? collect())
        ];
    }

}
