<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PermissionEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function authHeadersFor(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    public function test_permission_creation_endpoint_exists()
    {
        // Teste básico para verificar se o endpoint existe
        $response = $this->postJson('/api/permissions', []);
        
        // Deve retornar 401 (não autenticado) ou 422 (dados inválidos)
        $this->assertTrue(in_array($response->status(), [401, 422]));
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
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $headers = $this->authHeadersFor($user);

        $response = $this->withHeaders($headers)->postJson('/api/permissions', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'module',
                    'action',
                    'resource'
                ]);
    }

    public function test_permission_creation_with_valid_data_structure()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $headers = $this->authHeadersFor($user);

        $permissionData = [
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'description' => 'Test permission description',
            'module' => 'test',
            'action' => 'permission',
            'resource' => 'test',
            'is_active' => true
        ];

        $response = $this->withHeaders($headers)->postJson('/api/permissions', $permissionData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['slug' => 'test.permission']);

        $this->assertDatabaseHas('permissions', [
            'slug' => 'test.permission',
            'tenant_id' => $tenant->id,
        ]);
    }
}
