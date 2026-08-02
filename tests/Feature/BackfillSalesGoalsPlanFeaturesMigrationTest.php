<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobre a migration 2026_08_02_120000_backfill_sales_goals_plan_features,
 * que corrige o bug de 'sales_goals' nunca ficar habilitado em nenhum plano
 * (a migration original que criou a feature só populou feature_definitions,
 * nunca plan_features).
 */
class BackfillSalesGoalsPlanFeaturesMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function runBackfillMigration(): void
    {
        $migration = require base_path('database/migrations/2026_08_02_120000_backfill_sales_goals_plan_features.php');
        $migration->up();
    }

    public function test_backfill_enables_sales_goals_for_professional_and_enterprise_plans(): void
    {
        $basic = Plan::create(['name' => 'Plano Básico', 'url' => 'plano-basico', 'price' => 199, 'is_active' => true]);
        $professional = Plan::create(['name' => 'Plano Profissional', 'url' => 'plano-profissional', 'price' => 399, 'is_active' => true]);
        $enterprise = Plan::create(['name' => 'Plano Enterprise', 'url' => 'plano-enterprise', 'price' => 799, 'is_active' => true]);

        $this->runBackfillMigration();

        $this->assertDatabaseHas('plan_features', [
            'plan_id' => $basic->id, 'feature_key' => 'sales_goals', 'is_enabled' => false,
        ]);
        $this->assertDatabaseHas('plan_features', [
            'plan_id' => $professional->id, 'feature_key' => 'sales_goals', 'is_enabled' => true,
        ]);
        $this->assertDatabaseHas('plan_features', [
            'plan_id' => $enterprise->id, 'feature_key' => 'sales_goals', 'is_enabled' => true,
        ]);
    }

    public function test_backfill_is_idempotent_and_does_not_duplicate_rows(): void
    {
        $plan = Plan::create(['name' => 'Plano Enterprise', 'url' => 'plano-enterprise', 'price' => 799, 'is_active' => true]);

        $this->runBackfillMigration();
        $this->runBackfillMigration();

        $this->assertEquals(
            1,
            PlanFeature::where('plan_id', $plan->id)->where('feature_key', 'sales_goals')->count()
        );
    }

    public function test_enterprise_tenant_can_access_sales_goals_endpoint_after_backfill(): void
    {
        $plan = Plan::create(['name' => 'Plano Enterprise', 'url' => 'plano-enterprise', 'price' => 799, 'is_active' => true]);
        $this->runBackfillMigration();

        $tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/sales-goals');

        $response->assertStatus(200);
    }

    public function test_basic_tenant_is_still_denied_sales_goals_endpoint_after_backfill(): void
    {
        $plan = Plan::create(['name' => 'Plano Básico', 'url' => 'plano-basico', 'price' => 199, 'is_active' => true]);
        $this->runBackfillMigration();

        $tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/sales-goals');

        $response->assertStatus(403)
            ->assertJson(['error_code' => 'PLAN_FEATURE_NOT_AVAILABLE']);
    }
}
