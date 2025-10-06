<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimplePermissionCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar tenant simples
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.local',
            'is_active' => true
        ]);
        
        // Criar usuário simples
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id
        ]);
    }

    public function test_authenticated_user_can_create_permission_with_valid_data()
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

    public function test_permission_creation_requires_authentication()
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

    public function test_permission_creation_validates_required_fields()
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

    public function test_permission_creation_validates_unique_slug_per_tenant()
    {
        $this->actingAs($this->user);

        // Criar permissão existente
        Permission::create([
            'name' => 'Existing Permission',
            'slug' => 'existing.permission',
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test',
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

    public function test_permission_creation_allows_same_slug_different_tenants()
    {
        $this->actingAs($this->user);

        // Criar outro tenant
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'domain' => 'other.local',
            'is_active' => true
        ]);

        // Criar permissão em outro tenant
        Permission::create([
            'name' => 'Other Permission',
            'slug' => 'shared.permission',
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test',
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

    public function test_permission_creation_sets_default_values()
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
}
