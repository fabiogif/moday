<?php

namespace Tests\Feature\Visit;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class VisitCrudTest extends TestCase
{
    use GrantsDistribtecPermissions;

    private User $user;
    private Tenant $tenant;
    private Client $client;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantDistribtecPermissions($this->user, $this->tenant->id);
        $this->client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'scheduled_start_time' => '09:00',
            'scheduled_end_time' => '10:00',
            'type' => 'venda',
            'priority' => 'normal',
            'objective_notes' => 'Apresentar novos produtos',
        ], $overrides);
    }

    #[Test]
    public function guest_cannot_access_visits(): void
    {
        $this->getJson('/api/visits')->assertStatus(401);
    }

    #[Test]
    public function it_creates_a_visit(): void
    {
        $response = $this->withHeaders($this->auth())
            ->postJson('/api/visits', $this->payload());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'agendada')
            ->assertJsonPath('data.type', 'venda')
            ->assertJsonPath('data.client.id', $this->client->id);

        $this->assertDatabaseHas('visits', [
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'user_id' => $this->user->id,
            'status' => 'agendada',
        ]);

        $this->assertDatabaseHas('visit_status_histories', [
            'to_status' => 'agendada',
            'from_status' => null,
        ]);

        $this->assertDatabaseHas('visit_audit_logs', [
            'action' => 'created',
        ]);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/api/visits', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['client_id', 'scheduled_date', 'scheduled_start_time', 'scheduled_end_time', 'type']);
    }

    #[Test]
    public function it_rejects_end_time_before_start_time(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/api/visits', $this->payload(['scheduled_start_time' => '10:00', 'scheduled_end_time' => '09:00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_end_time']);
    }

    #[Test]
    public function it_lists_only_tenant_visits(): void
    {
        $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload())->assertStatus(201);

        $otherPlan = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        $otherClient = Client::factory()->create(['tenant_id' => $otherTenant->id]);
        \App\Models\Visit::factory()->create([
            'tenant_id' => $otherTenant->id,
            'client_id' => $otherClient->id,
            'user_id' => User::factory()->create(['tenant_id' => $otherTenant->id])->id,
        ]);

        $response = $this->withHeaders($this->auth())->getJson('/api/visits');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function it_shows_a_visit_with_client_alert(): void
    {
        $store = $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload());
        $uuid = $store->json('data.uuid');

        $response = $this->withHeaders($this->auth())->getJson("/api/visits/{$uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $uuid)
            ->assertJsonStructure(['data' => ['client_alert' => ['overdue', 'overdue_amount', 'credit_limit', 'credit_available', 'is_vip']]]);
    }

    #[Test]
    public function it_returns_404_for_unknown_visit(): void
    {
        $this->withHeaders($this->auth())
            ->getJson('/api/visits/' . \Illuminate\Support\Str::uuid())
            ->assertStatus(404);
    }

    #[Test]
    public function it_updates_a_visit(): void
    {
        $store = $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload());
        $uuid = $store->json('data.uuid');

        $response = $this->withHeaders($this->auth())
            ->putJson("/api/visits/{$uuid}", ['priority' => 'urgente', 'notes' => 'Cliente pediu para adiantar']);

        $response->assertStatus(200)
            ->assertJsonPath('data.priority', 'urgente')
            ->assertJsonPath('data.notes', 'Cliente pediu para adiantar');

        $this->assertDatabaseHas('visit_audit_logs', ['action' => 'updated']);
    }

    #[Test]
    public function it_deletes_a_visit(): void
    {
        $store = $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload());
        $uuid = $store->json('data.uuid');

        $this->withHeaders($this->auth())
            ->deleteJson("/api/visits/{$uuid}")
            ->assertStatus(200);

        $this->assertSoftDeleted('visits', ['uuid' => $uuid]);
        $this->assertDatabaseHas('visit_audit_logs', ['action' => 'deleted']);
    }

    #[Test]
    public function it_dedupes_creation_by_client_request_id(): void
    {
        $payload = $this->payload(['client_request_id' => 'field-app-123']);

        $first = $this->withHeaders($this->auth())->postJson('/api/visits', $payload);
        $second = $this->withHeaders($this->auth())->postJson('/api/visits', $payload);

        $first->assertStatus(201);
        $second->assertStatus(201);
        $this->assertSame($first->json('data.uuid'), $second->json('data.uuid'));
        $this->assertDatabaseCount('visits', 1);
    }
}
