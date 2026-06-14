<?php

namespace App\Http\Resources\Integrations\Ifood;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IfoodCatalogSnapshotResource extends JsonResource
{
    /**
     * @param \App\Models\Integrations\Ifood\IfoodCatalogSnapshot $resource
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'catalog_id' => $this->catalog_id,
            'group_id' => $this->group_id,
            'snapshot_type' => $this->snapshot_type,
            'captured_at' => $this->captured_at?->toIso8601String(),
            'payload' => $this->payload,
        ];
    }
}

