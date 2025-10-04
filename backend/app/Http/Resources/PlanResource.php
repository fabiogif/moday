<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'price' => $this->price,
            //'details' => DetailPlanResource::collection($this->details)
            'details' => $this->details
        ];
    }
}
