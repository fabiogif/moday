<?php

namespace Tests\Feature\Visit;

use App\Models\Client;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class VisitReportTest extends TestCase
{
    private Tenant $tenant;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function grantOnly(User $user, array $slugs): void
    {
        $ids = [];
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug, 'tenant_id' => $this->tenant->id],
                ['name' => $slug, 'description' => $slug, 'module' => 'visits', 'action' => $slug, 'resource' => 'visits', 'is_active' => true]
            );
            $ids[] = $permission->id;
        }
        $user->permissions()->syncWithoutDetaching($ids);
    }

    private function auth(User $user): array
    {
        return ['Authorization' => 'Bearer ' . JWTAuth::fromUser($user)];
    }

    #[Test]
    public function seller_without_view_all_only_sees_own_report(): void
    {
        $sellerA = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $sellerB = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantOnly($sellerA, ['visits.reports.index']);

        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerA->id, 'client_id' => $this->client->id, 'scheduled_date' => now()->format('Y-m-d'), 'status' => 'concluida', 'result' => 'venda_realizada', 'order_value' => 100]);
        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerB->id, 'client_id' => $this->client->id, 'scheduled_date' => now()->format('Y-m-d'), 'status' => 'concluida', 'result' => 'venda_realizada', 'order_value' => 500]);

        $response = $this->withHeaders($this->auth($sellerA))->getJson('/api/visits/reports');

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('data.total_visits'));
        $this->assertEquals(100.0, $response->json('data.conversion.total_order_value'));
        $this->assertNull($response->json('data.by_seller'));
    }

    #[Test]
    public function supervisor_with_view_all_sees_breakdown_by_seller(): void
    {
        $sellerA = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $sellerB = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $supervisor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantOnly($supervisor, ['visits.reports.index', 'visits.view-all']);

        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerA->id, 'client_id' => $this->client->id, 'scheduled_date' => now()->format('Y-m-d'), 'status' => 'concluida', 'result' => 'venda_realizada', 'order_value' => 100]);
        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerB->id, 'client_id' => $this->client->id, 'scheduled_date' => now()->format('Y-m-d'), 'status' => 'agendada']);

        $response = $this->withHeaders($this->auth($supervisor))->getJson('/api/visits/reports');

        $response->assertStatus(200);
        $this->assertSame(2, $response->json('data.total_visits'));
        $this->assertCount(2, $response->json('data.by_seller'));
        $this->assertSame(1, $response->json('data.conversion.sales_closed'));
    }

    #[Test]
    public function seller_without_reports_permission_is_forbidden(): void
    {
        $seller = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth($seller))->getJson('/api/visits/reports')->assertStatus(403);
    }
}
