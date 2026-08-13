<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CategoryBasicTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar tenant
        $this->tenant = Tenant::factory()->create();
        
        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        // Gerar token JWT
        $this->token = JWTAuth::fromUser($this->user);
    }

    #[Test]
    public function pode_criar_categoria_com_sucesso()
    {
        $categoryData = [
            'name' => 'Churrasco',
            'description' => 'Categoria para produtos de churrasco',
            'url' => 'churrasco'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/category', $categoryData);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Churrasco', $response->json('data.name'));
    }

    #[Test]
    public function nao_pode_criar_categoria_sem_autenticacao()
    {
        $categoryData = [
            'name' => 'Churrasco',
            'description' => 'Categoria para produtos de churrasco'
        ];

        $response = $this->postJson('/api/category', $categoryData);
        $response->assertStatus(401);
    }

    #[Test]
    public function valida_campos_obrigatorios_para_categoria()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/category', []);

        $response->assertStatus(422);
    }

    #[Test]
    public function pode_listar_categorias()
    {
        // Criar algumas categorias
        Category::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/category');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data'));
    }
}
