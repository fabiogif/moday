<?php

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Migrations\Migration;

/**
 * A migration 2026_06_20_000003_seed_sales_goals_feature_flag criou a
 * definição da feature 'sales_goals' em feature_definitions, mas nunca
 * populou plan_features para os planos existentes — resultado: a feature
 * nunca fica habilitada em nenhum plano, mesmo Enterprise (hasFeature()
 * sempre cai no fallback false). Esta migration corrige isso reaplicando
 * a mesma detecção de tier usada em FeatureDefinitionsSeeder.
 */
return new class extends Migration
{
    private const FEATURE_KEY = 'sales_goals';

    /** Mantido idêntico ao FeatureDefinitionsSeeder::PLAN_TIER_FEATURES para 'sales_goals' */
    private const ENABLED_TIERS = ['professional', 'enterprise'];

    public function up(): void
    {
        Plan::all()->each(function (Plan $plan) {
            $enabled = in_array($this->detectTier($plan), self::ENABLED_TIERS, true);

            PlanFeature::updateOrCreate(
                ['plan_id' => $plan->id, 'feature_key' => self::FEATURE_KEY],
                ['is_enabled' => $enabled]
            );
        });
    }

    public function down(): void
    {
        PlanFeature::where('feature_key', self::FEATURE_KEY)->delete();
    }

    /** Cópia de FeatureDefinitionsSeeder::detectTier() para manter a mesma heurística. */
    private function detectTier(Plan $plan): string
    {
        $haystack = strtolower(($plan->url ?? '') . ' ' . ($plan->name ?? ''));

        if (str_contains($haystack, 'enterprise')) {
            return 'enterprise';
        }
        if (str_contains($haystack, 'profissional') || str_contains($haystack, 'professional')) {
            return 'professional';
        }

        if ((float) $plan->price >= 600) {
            return 'enterprise';
        }
        if ((float) $plan->price >= 300) {
            return 'professional';
        }

        return 'basic';
    }
};
