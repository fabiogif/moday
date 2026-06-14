<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'rg' => $this->rg,
            'job_position_id' => $this->job_position_id,
            'is_active' => $this->is_active,
            'tenant_id' => $this->tenant_id,
            'last_login_at' => $this->last_login_at,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'profiles' => ProfileResource::collection($this->whenLoaded('profiles')),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'job_position' => new JobPositionResource($this->whenLoaded('jobPosition')),
        ];
    }
}