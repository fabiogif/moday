<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Testes manuais para Category
 * 
 * Para executar:
 * 1. Certifique-se de que o Docker está rodando
 * 2. Execute: docker-compose exec app php artisan test tests/Feature/CategoryManualTest.php
 * 3. Ou execute: php artisan test tests/Feature/CategoryManualTest.php (se as extensões estiverem disponíveis)
 */
class CategoryManualTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Teste: Criar categoria com sucesso
     */
    public function test_criar_categoria_sucesso()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = JWTAuth::fromUser($user);

        $categoryData = [
            'name' => 'Churrasco',
            'description' => 'Categoria para produtos de churrasco',
            'url' => 'churrasco'
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/category', $categoryData);

        // Assert
        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Churrasco', $response->json('data.name'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Churrasco',
            'tenant_id' => $tenant->id
        ]);
    }

    /**
     * Teste: Não pode criar categoria sem autenticação
     */
    public function test_criar_categoria_sem_auth()
    {
        $categoryData = [
            'name' => 'Churrasco',
            'description' => 'Categoria para produtos de churrasco'
        ];

        $response = $this->postJson('/api/category', $categoryData);
        $response->assertStatus(401);
    }

    /**
     * Teste: Listar categorias
     */
    public function test_listar_categorias()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = JWTAuth::fromUser($user);

        Category::factory()->count(3)->create(['tenant_id' => $tenant->id]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/category');

        // Assert
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Teste: Buscar categoria por ID
     */
    public function test_buscar_categoria_por_id()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = JWTAuth::fromUser($user);

        $category = Category::factory()->create(['tenant_id' => $tenant->id]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson("/api/category/{$category->uuid}");

        // Assert
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals($category->uuid, $response->json('data.identify'));
    }

    /**
     * Teste: Atualizar categoria
     */
    public function test_atualizar_categoria()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = JWTAuth::fromUser($user);

        $category = Category::factory()->create(['tenant_id' => $tenant->id]);

        $updateData = [
            'name' => 'Churrasco Atualizado',
            'description' => 'Descrição atualizada',
            'url' => 'churrasco-atualizado'
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->putJson("/api/category/{$category->id}", $updateData);

        // Assert
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Churrasco Atualizado', $response->json('data.name'));
    }

    /**
     * Teste: Deletar categoria
     */
    public function test_deletar_categoria()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = JWTAuth::fromUser($user);

        $category = Category::factory()->create(['tenant_id' => $tenant->id]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->deleteJson("/api/category/{$category->uuid}");

        // Assert
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /**
     * Teste: Validação de campos obrigatórios
     */
    public function test_validacao_campos_obrigatorios()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = JWTAuth::fromUser($user);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/category', []);

        // Assert
        $response->assertStatus(422);
        $this->assertArrayHasKey('errors', $response->json());
    }
}
