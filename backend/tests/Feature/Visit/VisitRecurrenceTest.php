<?php

namespace Tests\Feature\Visit;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VisitRecurrence;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class VisitRecurrenceTest extends TestCase
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
            'frequency' => 'weekly',
            'days_of_week' => [1],
            'scheduled_start_time' => '09:00',
            'scheduled_end_time' => '10:00',
            'type' => 'venda',
            'priority' => 'normal',
            'starts_on' => now()->addDay()->format('Y-m-d'),
        ], $overrides);
    }

    #[Test]
    public function it_creates_a_recurrence(): void
    {
        $response = $this->withHeaders($this->auth())->postJson('/api/visits/recurrences', $this->payload());

        $response->assertStatus(201)
            ->assertJsonPath('data.frequency', 'weekly')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('visit_recurrences', [
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'frequency' => 'weekly',
        ]);
    }

    #[Test]
    public function it_validates_days_of_week_for_weekly_frequency(): void
    {
        $payload = $this->payload();
        unset($payload['days_of_week']);

        $this->withHeaders($this->auth())
            ->postJson('/api/visits/recurrences', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['days_of_week']);
    }

    #[Test]
    public function it_lists_and_shows_recurrences(): void
    {
        $store = $this->withHeaders($this->auth())->postJson('/api/visits/recurrences', $this->payload());
        $uuid = $store->json('data.uuid');

        $this->withHeaders($this->auth())->getJson('/api/visits/recurrences')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->auth())->getJson("/api/visits/recurrences/{$uuid}")
            ->assertStatus(200)
            ->assertJsonPath('data.uuid', $uuid);
    }

    #[Test]
    public function it_updates_a_recurrence(): void
    {
        $store = $this->withHeaders($this->auth())->postJson('/api/visits/recurrences', $this->payload());
        $uuid = $store->json('data.uuid');

        $this->withHeaders($this->auth())
            ->putJson("/api/visits/recurrences/{$uuid}", ['priority' => 'alta'])
            ->assertStatus(200)
            ->assertJsonPath('data.priority', 'alta');
    }

    #[Test]
    public function it_deactivates_a_recurrence_instead_of_deleting(): void
    {
        $store = $this->withHeaders($this->auth())->postJson('/api/visits/recurrences', $this->payload());
        $uuid = $store->json('data.uuid');

        $this->withHeaders($this->auth())->deleteJson("/api/visits/recurrences/{$uuid}")->assertStatus(200);

        $this->assertDatabaseHas('visit_recurrences', ['uuid' => $uuid, 'is_active' => false]);
    }

    #[Test]
    public function it_generates_visit_occurrences_within_the_window(): void
    {
        $recurrence = VisitRecurrence::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'user_id' => $this->user->id,
            'frequency' => 'daily',
            'interval_count' => 1,
            'starts_on' => now()->addDay()->format('Y-m-d'),
            'ends_on' => now()->addDays(4)->format('Y-m-d'),
        ]);

        $response = $this->withHeaders($this->auth())
            ->postJson("/api/visits/recurrences/{$recurrence->uuid}/generate", ['days' => 10]);

        $response->assertStatus(200);
        $this->assertCount(4, $response->json('data.created'));

        $this->assertDatabaseCount('visits', 4);
        $this->assertDatabaseHas('visits', ['recurrence_id' => $recurrence->id, 'status' => 'agendada']);
    }

    #[Test]
    public function generating_twice_does_not_duplicate_visits(): void
    {
        $recurrence = VisitRecurrence::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'user_id' => $this->user->id,
            'frequency' => 'daily',
            'interval_count' => 1,
            'starts_on' => now()->addDay()->format('Y-m-d'),
            'ends_on' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $this->withHeaders($this->auth())->postJson("/api/visits/recurrences/{$recurrence->uuid}/generate")->assertStatus(200);
        $this->withHeaders($this->auth())->postJson("/api/visits/recurrences/{$recurrence->uuid}/generate")->assertStatus(200);

        $this->assertDatabaseCount('visits', 2);
    }
}
