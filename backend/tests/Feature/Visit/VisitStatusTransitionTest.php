<?php

namespace Tests\Feature\Visit;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class VisitStatusTransitionTest extends TestCase
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

    private function createVisit(array $overrides = []): Visit
    {
        return Visit::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => 'agendada',
        ], $overrides));
    }

    #[Test]
    public function it_cancels_a_scheduled_visit_with_reason(): void
    {
        $visit = $this->createVisit();

        $response = $this->withHeaders($this->auth())
            ->postJson("/api/visits/{$visit->uuid}/status", ['status' => 'cancelada', 'reason' => 'Cliente remarcou']);

        $response->assertStatus(200)->assertJsonPath('data.status', 'cancelada');

        $this->assertDatabaseHas('visit_status_histories', [
            'visit_id' => $visit->id,
            'from_status' => 'agendada',
            'to_status' => 'cancelada',
            'reason' => 'Cliente remarcou',
        ]);
    }

    #[Test]
    public function it_requires_reason_to_cancel(): void
    {
        $visit = $this->createVisit();

        $this->withHeaders($this->auth())
            ->postJson("/api/visits/{$visit->uuid}/status", ['status' => 'cancelada'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    #[Test]
    public function it_marks_client_absent_with_reason(): void
    {
        $visit = $this->createVisit();

        $this->withHeaders($this->auth())
            ->postJson("/api/visits/{$visit->uuid}/status", ['status' => 'cliente_ausente', 'reason' => 'Estabelecimento fechado'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cliente_ausente');
    }

    #[Test]
    public function it_rejects_invalid_transition_from_terminal_status(): void
    {
        $visit = $this->createVisit(['status' => 'concluida']);

        $this->withHeaders($this->auth())
            ->postJson("/api/visits/{$visit->uuid}/status", ['status' => 'agendada'])
            ->assertStatus(422);
    }

    #[Test]
    public function it_reschedules_a_visit_creating_a_new_one_and_linking_it(): void
    {
        $visit = $this->createVisit([
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'scheduled_start_time' => '09:00',
            'scheduled_end_time' => '10:00',
        ]);

        $response = $this->withHeaders($this->auth())->postJson("/api/visits/{$visit->uuid}/status", [
            'status' => 'reagendada',
            'reason' => 'Vendedor pediu para adiar',
            'reschedule_to' => [
                'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
                'scheduled_start_time' => '14:00',
                'scheduled_end_time' => '15:00',
            ],
        ]);

        $response->assertStatus(200)->assertJsonPath('data.status', 'agendada');

        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'status' => 'reagendada']);
        $this->assertDatabaseHas('visits', [
            'rescheduled_from_visit_id' => $visit->id,
            'status' => 'agendada',
        ]);
        $this->assertDatabaseCount('visits', 2);
    }
}
