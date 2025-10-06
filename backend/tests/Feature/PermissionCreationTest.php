<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PermissionCreationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test.local'
        ]);
        
        // Criar usuário
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function authenticated_user_can_create_permission_with_valid_data()
    {
        $this->actingAs($this->user);

        $permissionData = [
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'description' => 'Test permission description',
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test',
            'is_active' => true
        ];

        $response = $this->postJson('/api/permissions', $permissionData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'module',
                        'action',
                        'resource',
                        'is_active',
                        'tenant_id'
                    ]
                ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function permission_creation_requires_authentication()
    {
        $permissionData = [
            'name' => 'Test Permission',
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test'
        ];

        $response = $this->postJson('/api/permissions', $permissionData);

        $response->assertStatus(401);
    }

    /** @test */
    public function permission_creation_validates_required_fields()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/permissions', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'module',
                    'action',
                    'resource'
                ]);
    }

    /** @test */
    public function permission_creation_validates_unique_slug_per_tenant()
    {
        $this->actingAs($this->user);

        // Criar permissão existente
        Permission::factory()->create([
            'slug' => 'existing.permission',
            'tenant_id' => $this->tenant->id
        ]);

        $permissionData = [
            'name' => 'Another Permission',
            'slug' => 'existing.permission', // Slug duplicado
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test'
        ];

        $response = $this->postJson('/api/permissions', $permissionData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function permission_creation_allows_same_slug_different_tenants()
    {
        $this->actingAs($this->user);

        // Criar outro tenant
        $otherTenant = Tenant::factory()->create([
            'name' => 'Other Tenant',
            'domain' => 'other.local'
        ]);

        // Criar permissão em outro tenant
        Permission::factory()->create([
            'slug' => 'shared.permission',
            'tenant_id' => $otherTenant->id
        ]);

        $permissionData = [
            'name' => 'Same Slug Permission',
            'slug' => 'shared.permission', // Mesmo slug, tenant diferente
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test'
        ];

        $response = $this->postJson('/api/permissions', $permissionData);

        $response->assertStatus(201);
    }

    /** @test */
    public function permission_creation_generates_slug_automatically_if_not_provided()
    {
        $this->actingAs($this->user);

        $permissionData = [
            'name' => 'Auto Slug Permission',
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test'
            // slug não fornecido
        ];

        $response = $this->postJson('/api/permissions', $permissionData);

        $response->assertStatus(201);

        $permission = Permission::where('name', 'Auto Slug Permission')->first();
        $this->assertNotNull($permission);
        $this->assertEquals('auto-slug-permission', $permission->slug);
    }

    /** @test */
    public function permission_creation_sets_default_values()
    {
        $this->actingAs($this->user);

        $permissionData = [
            'name' => 'Default Values Permission',
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test'
            // is_active não fornecido
        ];

        $response = $this->postJson('/api/permissions', $permissionData);

        $response->assertStatus(201);

        $permission = Permission::where('name', 'Default Values Permission')->first();
        $this->assertTrue($permission->is_active);
        $this->assertEquals($this->tenant->id, $permission->tenant_id);
    }

    /** @test */
    public function permission_creation_validates_field_lengths()
    {
        $this->actingAs($this->user);

        $permissionData = [
            'name' => str_repeat('a', 256), // Muito longo
            'slug' => str_repeat('b', 256), // Muito longo
            'description' => str_repeat('c', 501), // Muito longo
            'module' => str_repeat('d', 101), // Muito longo
            'action' => str_repeat('e', 101), // Muito longo
            'resource' => str_repeat('f', 101), // Muito longo
        ];

        $response = $this->postJson('/api/permissions', $permissionData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'slug',
                    'description',
                    'module',
                    'action',
                    'resource'
                ]);
    }

    /** @test */
    public function permission_creation_handles_boolean_is_active()
    {
        $this->actingAs($this->user);

        $permissionData = [
            'name' => 'Boolean Test Permission',
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test',
            'is_active' => false
        ];

        $response = $this->postJson('/api/permissions', $permissionData);

        $response->assertStatus(201);

        $permission = Permission::where('name', 'Boolean Test Permission')->first();
        $this->assertFalse($permission->is_active);
    }
}
