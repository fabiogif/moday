<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'title' => $this->title,
            'description' => $this->description,
            'goal_type' => $this->goal_type,
            'target_user_id' => $this->target_user_id,
            'target_profile_id' => $this->target_profile_id,
            'target_product_id' => $this->target_product_id,
            'period_type' => $this->period_type,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'target_value' => (float) $this->target_value,
            'current_value' => (float) $this->current_value,
            'completion_percent' => (float) $this->completion_percent,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'is_completed' => $this->current_value >= $this->target_value,
            'target_user' => $this->whenLoaded('targetUser', fn () => [
                'id' => $this->targetUser->id,
                'name' => $this->targetUser->name,
            ]),
            'target_profile' => $this->whenLoaded('targetProfile', fn () => [
                'id' => $this->targetProfile->id,
                'name' => $this->targetProfile->name,
            ]),
            'target_product' => $this->whenLoaded('targetProduct', fn () => [
                'id' => $this->targetProduct->id,
                'name' => $this->targetProduct->name,
            ]),
            'created_by' => $this->whenLoaded('createdByUser', fn () => [
                'id' => $this->createdByUser->id,
                'name' => $this->createdByUser->name,
            ]),
            'progress_logs' => SalesGoalProgressLogResource::collection($this->whenLoaded('progressLogs')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
