<?php

namespace Tests\Feature\Dashboard;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Profile $profile;
    private User $user;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->accessible()->create();
        $this->profile = Profile::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Operador',
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'profile_id' => $this->profile->id,
        ]);
        $this->headers = [
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($this->user),
            'Accept' => 'application/json',
        ];
    }

    #[Test]
    public function bloqueia_dashboard_sem_permissao(): void
    {
        $this->withHeaders($this->headers)
            ->getJson('/api/dashboard')
            ->assertForbidden();
    }

    #[Test]
    public function permite_dashboard_com_permissao(): void
    {
        $permission = $this->createDashboardPermission();
        $this->profile->permissions()->attach($permission);

        $this->withHeaders($this->headers)
            ->getJson('/api/dashboard')
            ->assertOk();
    }

    #[Test]
    public function auth_me_retorna_slugs_efetivos_do_perfil(): void
    {
        $permission = $this->createDashboardPermission();
        $this->profile->permissions()->attach($permission);

        $this->withHeaders($this->headers)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.permission_slugs.0', 'dashboard.index');
    }

    #[Test]
    public function sincronizar_perfil_invalida_cache_de_permissoes_do_usuario(): void
    {
        config()->set('acl.cache.enabled', true);
        $permission = $this->createDashboardPermission();

        $this->assertSame([], $this->user->getPermissionsList());

        $this->withHeaders($this->headers)
            ->putJson("/api/profile/{$this->profile->id}/permissions/sync", [
                'permission_ids' => [$permission->id],
            ])
            ->assertOk();

        $this->assertContains('dashboard.index', $this->user->fresh()->getPermissionsList());
    }

    #[Test]
    public function backfill_mantem_acesso_dos_perfis_existentes(): void
    {
        $this->artisan('acl:backfill-dashboard-permission')
            ->assertSuccessful();

        $permission = Permission::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('slug', 'dashboard.index')
            ->firstOrFail();

        $this->assertDatabaseHas('permission_profiles', [
            'profile_id' => $this->profile->id,
            'permission_id' => $permission->id,
        ]);
    }

    private function createDashboardPermission(): Permission
    {
        return Permission::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Visualizar Dashboard',
            'slug' => 'dashboard.index',
            'module' => 'dashboard',
            'action' => 'index',
            'resource' => 'dashboard',
            'is_active' => true,
        ]);
    }
}
