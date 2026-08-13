<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Resolve enabled features from plan_features + definition
        $features = $this->whenLoaded('features', function () {
            return $this->features
                ->filter(fn($pf) => $pf->is_enabled)
                ->map(fn($pf) => [
                    'key'          => $pf->feature_key,
                    'name'         => $pf->display_name ?? ($pf->definition?->name ?? $pf->feature_key),
                    'category'     => $pf->definition?->category ?? 'outros',
                    'description'  => $pf->definition?->description,
                    'is_enabled'   => true,
                ])
                ->values();
        });

        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'url'                     => $this->url,
            'description'             => $this->description,
            'price'                   => $this->price,
            'is_active'               => $this->is_active,
            'max_users'               => $this->max_users,
            'max_products'            => $this->max_products,
            'max_orders_per_month'    => $this->max_orders_per_month,
            'has_marketing'           => $this->has_marketing,
            'has_order_completion_email' => $this->has_order_completion_email,
            'has_reports'             => $this->has_reports,
            'features'                => $features,
            'details'                 => $this->details,
        ];
    }
}
