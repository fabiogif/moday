<?php

namespace Tests\Feature;

use App\Models\JobPosition;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use App\Http\Middleware\Authenticate as AppAuthenticate;
use Illuminate\Auth\Middleware\Authenticate as LaravelAuthenticate;

class UserRgJobPositionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tenant $tenant;
    private JobPosition $position;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);

        $profile = Profile::factory()->create(['tenant_id' => $this->tenant->id]);
        $createPerm = Permission::factory()->create(['slug' => 'users.create', 'name' => 'Criar Funcionários']);
        $updatePerm = Permission::factory()->create(['slug' => 'users.update', 'name' => 'Editar Funcionários']);
        $profile->permissions()->attach([$createPerm->id, $updatePerm->id]);

        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $this->admin->profiles()->sync([$profile->id]);

        $this->position = JobPosition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Motorista',
        ]);

        $this->withoutMiddleware([
            JWTAuthenticate::class,
            AppAuthenticate::class,
            LaravelAuthenticate::class,
        ]);

        $this->actingAs($this->admin);
    }

    #[Test]
    public function it_can_create_user_with_rg_and_job_position(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'João Silva',
            'email' => 'joao@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'rg' => '12.345.678-9',
            'job_position_id' => $this->position->id,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.rg', '12.345.678-9')
            ->assertJsonPath('data.job_position_id', $this->position->id);

        $this->assertDatabaseHas('users', [
            'email' => 'joao@test.com',
            'rg' => '12.345.678-9',
            'job_position_id' => $this->position->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function it_can_update_user_rg_and_job_position(): void
    {
        $employee = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rg' => null,
            'job_position_id' => null,
        ]);

        $newPosition = JobPosition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Entregador',
        ]);

        $response = $this->putJson("/api/users/{$employee->id}", [
            'name' => $employee->name,
            'email' => $employee->email,
            'rg' => '98.765.432-1',
            'job_position_id' => $newPosition->id,
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.rg', '98.765.432-1')
            ->assertJsonPath('data.job_position_id', $newPosition->id);
    }
}
