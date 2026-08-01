<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ibge_code' => $this->ibge_code,
            'uf' => $this->uf,
            'name' => $this->name,
            'region' => $this->region,
        ];
    }
}
