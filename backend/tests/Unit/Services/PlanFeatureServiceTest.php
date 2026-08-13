<?php

namespace Tests\Unit\Services;

use App\Models\FeatureDefinition;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlanFeatureServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanFeatureService $service;
    private Plan $plan;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlanFeatureService();
        $this->plan    = Plan::factory()->create();
        $this->tenant  = Tenant::factory()->create(['plan_id' => $this->plan->id]);
    }

    private function ensureFeature(string $key): void
    {
        FeatureDefinition::firstOrCreate(
            ['key' => $key],
            ['name' => $key, 'category' => 'test', 'plan_tier' => 'basic', 'is_active' => true]
        );
    }

    #[Test]
    public function hasFeature_returns_true_when_plan_feature_is_enabled(): void
    {
        $this->ensureFeature('b2b_payment_link');
        PlanFeature::create([
            'plan_id'     => $this->plan->id,
            'feature_key' => 'b2b_payment_link',
            'is_enabled'  => true,
        ]);

        $result = $this->service->hasFeature($this->tenant->id, 'b2b_payment_link');

        $this->assertTrue($result);
    }

    #[Test]
    public function hasFeature_returns_false_when_plan_feature_is_disabled(): void
    {
        $this->ensureFeature('b2b_payment_link');
        PlanFeature::create([
            'plan_id'     => $this->plan->id,
            'feature_key' => 'b2b_payment_link',
            'is_enabled'  => false,
        ]);

        $result = $this->service->hasFeature($this->tenant->id, 'b2b_payment_link');

        $this->assertFalse($result);
    }

    #[Test]
    public function hasFeature_falls_back_to_legacy_boolean_column(): void
    {
        $this->plan->update(['has_marketing' => true]);

        $result = $this->service->hasFeature($this->tenant->id, 'marketing');

        $this->assertTrue($result);
    }

    #[Test]
    public function hasFeature_returns_false_for_unknown_tenant(): void
    {
        $result = $this->service->hasFeature(99999, 'any_feature');

        $this->assertFalse($result);
    }

    #[Test]
    public function hasFeature_result_is_cached_after_first_call(): void
    {
        $this->ensureFeature('reports');
        PlanFeature::create([
            'plan_id'     => $this->plan->id,
            'feature_key' => 'reports',
            'is_enabled'  => true,
        ]);

        $this->service->hasFeature($this->tenant->id, 'reports');

        // Value is now in cache
        $this->assertTrue(Cache::has("plan_feature:{$this->tenant->id}:reports"));
        $this->assertTrue(Cache::get("plan_feature:{$this->tenant->id}:reports"));
    }

    #[Test]
    public function syncFeatures_upserts_plan_features(): void
    {
        FeatureDefinition::create(['key' => 'abc_reports', 'name' => 'ABC Reports', 'category' => 'analytics', 'plan_tier' => 'professional']);

        $this->service->syncFeatures($this->plan->id, [
            'abc_reports' => true,
        ]);

        $this->assertDatabaseHas('plan_features', [
            'plan_id'     => $this->plan->id,
            'feature_key' => 'abc_reports',
            'is_enabled'  => true,
        ]);
    }

    #[Test]
    public function syncFeatures_updates_existing_feature(): void
    {
        $this->ensureFeature('abc_reports');
        PlanFeature::create([
            'plan_id'     => $this->plan->id,
            'feature_key' => 'abc_reports',
            'is_enabled'  => true,
        ]);

        $this->service->syncFeatures($this->plan->id, ['abc_reports' => false]);

        $this->assertDatabaseHas('plan_features', [
            'plan_id'     => $this->plan->id,
            'feature_key' => 'abc_reports',
            'is_enabled'  => false,
        ]);
    }

    #[Test]
    public function syncFeatures_busts_cache_for_all_tenants_on_plan(): void
    {
        $tenant2 = Tenant::factory()->create(['plan_id' => $this->plan->id]);
        FeatureDefinition::create(['key' => 'feat_x', 'name' => 'X', 'category' => 'misc', 'plan_tier' => 'basic']);

        Cache::put("plan_feature:{$this->tenant->id}:feat_x", true, 300);
        Cache::put("plan_feature:{$tenant2->id}:feat_x", true, 300);

        $this->service->syncFeatures($this->plan->id, ['feat_x' => false]);

        $this->assertNull(Cache::get("plan_feature:{$this->tenant->id}:feat_x"));
        $this->assertNull(Cache::get("plan_feature:{$tenant2->id}:feat_x"));
    }

    #[Test]
    public function getFeaturesForPlan_merges_definitions_with_plan_status(): void
    {
        FeatureDefinition::create(['key' => 'feat_a', 'name' => 'Feature A', 'category' => 'sales', 'plan_tier' => 'basic', 'display_order' => 1, 'is_active' => true]);
        PlanFeature::create(['plan_id' => $this->plan->id, 'feature_key' => 'feat_a', 'is_enabled' => true]);

        $features = $this->service->getFeaturesForPlan($this->plan->id);

        $feat = $features->firstWhere('key', 'feat_a');
        $this->assertNotNull($feat);
        $this->assertTrue($feat['is_enabled']);
        $this->assertEquals('Feature A', $feat['name']);
    }
}
