<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'identify'    => $this->identify,
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'description' => $this->description,
            'capacity'    => $this->capacity,
            'created_at'  => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
            'created_at_formatted' => $this->created_at ? Carbon::parse($this->created_at)->format('d/m/Y') : null,
            'updated_at'  => $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}
