<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class CategoryResource extends JsonResource
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
            'identify' => $this->uuid,
            'description' => $this->description,
            'url' => $this->url,
            'status' => $this->status ?? 'A',
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('d/m/Y') : null
        ];
    }
}
