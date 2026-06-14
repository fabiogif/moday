<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'action' => $this->action,
            'user' => $this->user?->name ?? 'Sistema',
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

