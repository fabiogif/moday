<?php

namespace App\Http\Resources\Visit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VisitMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'url' => Storage::disk('public')->url($this->file_path),
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => [
                'id' => $this->uploadedBy->id,
                'name' => $this->uploadedBy->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
