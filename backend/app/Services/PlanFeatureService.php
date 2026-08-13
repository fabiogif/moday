<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\FeatureDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PlanFeatureService
{
    // Legacy boolean fields kept for backwards compatibility
    private const LEGACY_FEATURES = [
        'marketing'              => 'has_marketing',
        'reports'                => 'has_reports',
        'order_completion_email' => 'has_order_completion_email',
    ];

    public function hasFeature(int $tenantId, string $featureKey): bool
    {
        $cacheKey = "plan_feature:{$tenantId}:{$featureKey}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $featureKey) {
            $plan = Plan::whereHas('tenants', fn ($q) => $q->where('id', $tenantId))
                ->with(['features' => fn ($q) => $q->where('feature_key', $featureKey)])
                ->first();

            if (!$plan) {
                return false;
            }

            // Check new plan_features table first
            $planFeature = $plan->features->first();
            if ($planFeature !== null) {
                return $planFeature->is_enabled;
            }

            // Fall back to legacy boolean columns
            if (isset(self::LEGACY_FEATURES[$featureKey])) {
                $column = self::LEGACY_FEATURES[$featureKey];
                return (bool) ($plan->{$column} ?? false);
            }

            return false;
        });
    }

    public function getEnabledFeatures(int $planId): Collection
    {
        return PlanFeature::where('plan_id', $planId)
            ->where('is_enabled', true)
            ->with('definition')
            ->get();
    }

    public function getFeaturesForPlan(int $planId): Collection
    {
        $definitions = FeatureDefinition::where('is_active', true)
            ->orderBy('category')
            ->orderBy('display_order')
            ->get();

        $planFeatures = PlanFeature::where('plan_id', $planId)
            ->get()
            ->keyBy('feature_key');

        return $definitions->map(function (FeatureDefinition $def) use ($planFeatures) {
            $pf = $planFeatures->get($def->key);
            return [
                'key'           => $def->key,
                'name'          => $pf?->display_name ?? $def->name,
                'description'   => $def->description,
                'category'      => $def->category,
                'icon'          => $def->icon,
                'plan_tier'     => $def->plan_tier,
                'display_order' => $def->display_order,
                'is_enabled'    => $pf?->is_enabled ?? false,
            ];
        });
    }

    /** Upsert plan features from array of ['key' => bool] */
    public function syncFeatures(int $planId, array $features): void
    {
        foreach ($features as $key => $enabled) {
            PlanFeature::updateOrCreate(
                ['plan_id' => $planId, 'feature_key' => $key],
                ['is_enabled' => (bool) $enabled]
            );
        }

        // Bust cache for all tenants on this plan
        $this->bustCacheForPlan($planId);
    }

    public function bustCacheForTenant(int $tenantId): void
    {
        $definitions = FeatureDefinition::pluck('key');
        foreach ($definitions as $key) {
            Cache::forget("plan_feature:{$tenantId}:{$key}");
        }
    }

    private function bustCacheForPlan(int $planId): void
    {
        $tenantIds = Plan::find($planId)?->tenants()->pluck('id') ?? collect();
        foreach ($tenantIds as $tenantId) {
            $this->bustCacheForTenant($tenantId);
        }
    }
}
