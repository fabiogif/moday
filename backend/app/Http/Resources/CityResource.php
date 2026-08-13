<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'ibge_code' => $this->ibge_code,
            'name' => $this->name,
            'is_capital' => (bool) $this->is_capital,
        ];

        if ($this->relationLoaded('state') && $this->state) {
            $data['state'] = (new StateResource($this->state))->resolve();
        }

        return $data;
    }
}
