<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => match ($this->type) {
                'receita' => 'Receita',
                'despesa' => 'Despesa',
                'ambos' => 'Receita e Despesa',
                default => $this->type,
            },
            'applies_to_payable' => (bool) $this->applies_to_payable,
            'applies_to_receivable' => (bool) $this->applies_to_receivable,
            'description' => $this->description,
            'color' => $this->color ?? '#3b82f6',
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
