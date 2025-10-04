<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
        ];
    }
}
