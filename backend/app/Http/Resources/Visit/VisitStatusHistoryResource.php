<?php

namespace App\Http\Resources\Visit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'changed_by' => $this->whenLoaded('changedBy', fn () => $this->changedBy?->name),
            'reason' => $this->reason,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
