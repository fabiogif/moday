<?php

namespace Tests\Feature;

use App\Models\JobPosition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use App\Http\Middleware\Authenticate as AppAuthenticate;
use Illuminate\Auth\Middleware\Authenticate as LaravelAuthenticate;

class JobPositionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->withoutMiddleware([
            JWTAuthenticate::class,
            AppAuthenticate::class,
            LaravelAuthenticate::class,
        ]);

        $this->actingAs($this->user);
    }

    #[Test]
    public function it_can_list_job_positions_for_tenant(): void
    {
        JobPosition::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        JobPosition::factory()->create(); // outro tenant

        $response = $this->getJson('/api/job-positions');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_filter_active_positions_only(): void
    {
        JobPosition::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        JobPosition::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson('/api/job-positions?active_only=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_can_create_a_job_position(): void
    {
        $response = $this->postJson('/api/job-positions', [
            'name' => 'Motorista',
            'description' => 'Conduz veículos',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Motorista');

        $this->assertDatabaseHas('job_positions', [
            'name' => 'Motorista',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function it_validates_required_name(): void
    {
        $response = $this->postJson('/api/job-positions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_prevents_duplicate_name_within_tenant(): void
    {
        JobPosition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Entregador',
        ]);

        $response = $this->postJson('/api/job-positions', [
            'name' => 'Entregador',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function it_can_update_a_job_position(): void
    {
        $position = JobPosition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ajudante',
        ]);

        $response = $this->putJson("/api/job-positions/{$position->uuid}", [
            'name' => 'Ajudante Geral',
            'description' => 'Apoio operacional',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Ajudante Geral');
    }

    #[Test]
    public function it_can_soft_delete_a_job_position_without_users(): void
    {
        $position = JobPosition::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->deleteJson("/api/job-positions/{$position->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('job_positions', ['id' => $position->id]);
    }

    #[Test]
    public function it_prevents_deleting_position_with_assigned_users(): void
    {
        $position = JobPosition::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'job_position_id' => $position->id,
        ]);

        $response = $this->deleteJson("/api/job-positions/{$position->uuid}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('job_positions', [
            'id' => $position->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function it_prevents_accessing_position_from_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherPosition = JobPosition::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $this->getJson("/api/job-positions/{$otherPosition->uuid}")
            ->assertStatus(404);
    }

    #[Test]
    public function seed_defaults_cria_vendedor_sem_duplicar(): void
    {
        $service = app(\App\Services\JobPositionService::class);

        $service->seedDefaultsForTenant($this->tenant->id);
        $service->seedDefaultsForTenant($this->tenant->id);

        $this->assertDatabaseHas('job_positions', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Vendedor',
            'is_active' => true,
        ]);

        $this->assertSame(
            1,
            JobPosition::where('tenant_id', $this->tenant->id)->where('name', 'Vendedor')->count()
        );
    }
}
