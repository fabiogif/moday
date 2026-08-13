<?php

namespace Tests\Feature;

use App\Models\{User, Tenant, Profile, Permission};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tymon\JWTAuth\Facades\JWTAuth;

class PermissionProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('plan_profile')) {
            Schema::create('plan_profile', function (Blueprint $table) {
                $table->unsignedBigInteger('plan_id');
                $table->unsignedBigInteger('profile_id');
            });
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('plan_profile');
        parent::tearDown();
    }

    private function actingTenantUser(): array
    {
        $tenant = Tenant::factory()->accessible()->create(['is_active' => true]);
        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'profile_id' => $profile->id]);

        $headers = [
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
            'Accept' => 'application/json',
        ];

        return [$tenant, $user, $headers];
    }

    #[Test]
    public function it_can_list_profiles()
    {
        [$tenant, , $headers] = $this->actingTenantUser();

        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $permissionA = Permission::factory()->create(['tenant_id' => $tenant->id]);
        $permissionB = Permission::factory()->create(['tenant_id' => $tenant->id]);
        $profile->permissions()->sync([$permissionA->id, $permissionB->id]);

        $response = $this->withHeaders($headers)->getJson("/api/profile/{$profile->id}/permissions");
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function it_can_create_profile_with_permissions()
    {
        [$tenant, , $headers] = $this->actingTenantUser();

        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $permissionA = Permission::factory()->create(['tenant_id' => $tenant->id]);
        $permissionB = Permission::factory()->create(['tenant_id' => $tenant->id]);
        $profile->permissions()->sync([$permissionA->id, $permissionB->id]);

        $response = $this->withHeaders($headers)->postJson("/api/profiles", [
            'name' => 'Test Profile',
            'permissions' => [$permissionA->id, $permissionB->id]
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function it_validates_required_fields_on_create()
    {
        [$tenant, , $headers] = $this->actingTenantUser();

        $response = $this->withHeaders($headers)->postJson("/api/profiles", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'permissions']);
    }

    #[Test]
    public function it_can_update_profile_permissions()
    {
        [$tenant, , $headers] = $this->actingTenantUser();

        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $permissionA = Permission::factory()->create(['tenant_id' => $tenant->id]);
        $permissionB = Permission::factory()->create(['tenant_id' => $tenant->id]);
        $profile->permissions()->sync([$permissionA->id]);

        $response = $this->withHeaders($headers)->putJson("/api/profile/{$profile->id}/permissions/sync", [
            'permission_ids' => [$permissionB->id]
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('permission_profiles', [
            'profile_id' => $profile->id,
            'permission_id' => $permissionB->id,
        ]);
    }

    #[Test]
    public function it_can_delete_profile_without_users()
    {
        [$tenant] = $this->actingTenantUser();

        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $profile->delete();

        $this->assertModelMissing($profile);
    }

    #[Test]
    public function it_prevents_deleting_profile_with_users()
    {
        [$tenant] = $this->actingTenantUser();

        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'profile_id' => $profile->id]);
        $profile->delete();

        $this->assertModelExists($profile);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/profiles');
        $response->assertStatus(401);
    }
}


