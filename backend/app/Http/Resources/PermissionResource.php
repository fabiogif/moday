<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'module' => $this->module,
            'action' => $this->action,
            'resource' => $this->resource, // Este é o campo 'resource' da tabela, não o objeto completo
            'group' => $this->group,
            'is_active' => $this->is_active,
            'tenant_id' => $this->tenant_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Remover relacionamentos para evitar recursão e dados desnecessários
            'profiles_count' => $this->when(isset($this->profiles_count), $this->profiles_count),
        ];
    }
}
