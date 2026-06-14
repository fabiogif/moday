<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CategorySimpleTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_criar_categoria_com_sucesso()
    {
        // Criar tenant
        $tenant = Tenant::factory()->create();
        
        // Criar usuário
        $user = User::factory()->create([
            'tenant_id' => $tenant->id
        ]);
        
        // Gerar token JWT
        $token = JWTAuth::fromUser($user);

        $categoryData = [
            'name' => 'Churrasco',
            'description' => 'Categoria para produtos de churrasco',
            'url' => 'churrasco'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/category', $categoryData);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Churrasco', $response->json('data.name'));
    }

    public function test_nao_pode_criar_categoria_sem_autenticacao()
    {
        $categoryData = [
            'name' => 'Churrasco',
            'description' => 'Categoria para produtos de churrasco'
        ];

        $response = $this->postJson('/api/category', $categoryData);
        $response->assertStatus(401);
    }

    public function test_pode_listar_categorias()
    {
        // Criar tenant
        $tenant = Tenant::factory()->create();
        
        // Criar usuário
        $user = User::factory()->create([
            'tenant_id' => $tenant->id
        ]);
        
        // Gerar token JWT
        $token = JWTAuth::fromUser($user);

        // Criar algumas categorias
        Category::factory()->count(3)->create([
            'tenant_id' => $tenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/category');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data'));
    }
}
