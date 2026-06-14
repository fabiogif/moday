<?php

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $usageLimit = $this->usage_limit;
        $remainingUses = $this->remainingUses();

        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'max_discount_amount' => $this->max_discount_amount ? (float) $this->max_discount_amount : null,
            'minimum_order_amount' => $this->minimum_order_amount ? (float) $this->minimum_order_amount : null,
            'usage_limit' => $usageLimit,
            'usage_limit_per_client' => $this->usage_limit_per_client,
            'times_redeemed' => $this->times_redeemed,
            'remaining_uses' => $remainingUses,
            'start_at' => optional($this->start_at)->toIso8601String(),
            'end_at' => optional($this->end_at)->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'is_featured' => (bool) $this->is_featured,
            'metadata' => $this->metadata,
            'image_path' => ImageHelper::normalizeToStoragePath($this->image),
            'image_url' => ImageHelper::publicAssetPath($this->image, 'public'),
            'status' => $this->resolveStatus(),
            'discount_display' => $this->formatDiscount(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }

    private function resolveStatus(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isScheduled()) {
            return 'scheduled';
        }

        return 'active';
    }

    private function formatDiscount(): string
    {
        if ($this->discount_type === 'percentage') {
            return number_format((float) $this->discount_value, 2, ',', '.') . '%';
        }

        return 'R$ ' . number_format((float) $this->discount_value, 2, ',', '.');
    }
}
