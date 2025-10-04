<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            'level' => $this->level,
            'is_active' => $this->is_active,
            'tenant_id' => $this->tenant_id,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'permissions_count' => $this->when($this->permissions_count !== null, $this->permissions_count),
            'users_count' => $this->when($this->users_count !== null, $this->users_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
