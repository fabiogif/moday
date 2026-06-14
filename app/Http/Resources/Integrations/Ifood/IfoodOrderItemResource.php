<?php

namespace App\Http\Resources\Integrations\Ifood;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IfoodOrderItemResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'sequence' => $this->sequence,
            'catalog_item_id' => $this->external_item_id,
            'unique_id' => $this->unique_id,
            'external_code' => $this->external_code,
            'sku' => $this->sku,
            'ean' => $this->ean,
            'name' => $this->name,
            'quantity' => $this->quantity_value ?? $this->quantity,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price !== null ? (float) $this->unit_price : null,
            'price' => $this->price !== null ? (float) $this->price : null,
            'total_price' => $this->total_price !== null ? (float) $this->total_price : null,
            'options_price' => $this->options_price !== null ? (float) $this->options_price : null,
            'customization_price' => $this->customization_price !== null ? (float) $this->customization_price : null,
            'observations' => $this->observations,
            'options' => $this->options ?? [],
            'scale_prices' => $this->scale_prices ?? [],
            'metadata' => $this->metadata ?? [],
        ];
    }
}

