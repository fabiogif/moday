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

class VisitConflictDetectionTest extends TestCase
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
        ], $overrides);
    }

    #[Test]
    public function it_blocks_overlapping_visit_for_the_same_seller(): void
    {
        $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload())->assertStatus(201);

        $response = $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload([
            'scheduled_start_time' => '09:30',
            'scheduled_end_time' => '10:30',
        ]));

        $response->assertStatus(409)
            ->assertJsonStructure(['success', 'message', 'conflicting_visit']);

        $this->assertDatabaseCount('visits', 1);
    }

    #[Test]
    public function it_allows_back_to_back_visits_without_overlap(): void
    {
        $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload())->assertStatus(201);

        $response = $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload([
            'scheduled_start_time' => '10:00',
            'scheduled_end_time' => '11:00',
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseCount('visits', 2);
    }

    #[Test]
    public function it_allows_forcing_an_overlapping_visit(): void
    {
        $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload())->assertStatus(201);

        $response = $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload([
            'scheduled_start_time' => '09:30',
            'scheduled_end_time' => '10:30',
            'force' => true,
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseCount('visits', 2);
    }

    #[Test]
    public function it_does_not_conflict_across_different_sellers(): void
    {
        $otherSeller = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload())->assertStatus(201);

        $response = $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload([
            'user_id' => $otherSeller->id,
            'scheduled_start_time' => '09:30',
            'scheduled_end_time' => '10:30',
        ]));

        $response->assertStatus(201);
    }

    #[Test]
    public function it_blocks_overlap_on_update_when_moving_into_another_visit_slot(): void
    {
        $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload())->assertStatus(201);

        $second = $this->withHeaders($this->auth())->postJson('/api/visits', $this->payload([
            'scheduled_start_time' => '11:00',
            'scheduled_end_time' => '12:00',
        ]));

        $response = $this->withHeaders($this->auth())->putJson('/api/visits/' . $second->json('data.uuid'), [
            'scheduled_start_time' => '09:30',
            'scheduled_end_time' => '10:30',
        ]);

        $response->assertStatus(409);
    }
}
