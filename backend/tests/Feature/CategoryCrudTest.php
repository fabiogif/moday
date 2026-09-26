<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    // ==========================================
    // TESTES DE INSERÇÃO (POST /api/category)
    // ==========================================

    #[Test]
    public function pode_inserir_categoria_com_todos_os_campos(): void
    {
        $payload = [
            'name' => 'Lanches Especiais',
            'description' => 'Hambúrgueres artesanais e combos',
            'order' => 3,
            'isActive' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/category', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'name',
                    'identify',
                    'description',
                    'url',
                    'order',
                    'status',
                    'created_at',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Lanches Especiais',
                    'description' => 'Hambúrgueres artesanais e combos',
                    'order' => 3,
                    'status' => 'A',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Lanches Especiais',
            'description' => 'Hambúrgueres artesanais e combos',
            'order' => 3,
            'tenant_id' => $this->tenant->id,
            'status' => 'A',
            'is_active' => 1,
        ]);
    }

    #[Test]
    public function pode_inserir_categoria_com_campos_minimos(): void
    {
        $payload = [
            'name' => 'Sobremesas',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/category', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Sobremesas',
                    'order' => 0,
                    'status' => 'A',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Sobremesas',
            'order' => 0,
            'tenant_id' => $this->tenant->id,
            'status' => 'A',
        ]);
    }

    #[Test]
    public function nao_pode_inserir_categoria_sem_autenticacao(): void
    {
        $response = $this->postJson('/api/category', [
            'name' => 'Bebidas',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function validacao_ao_inserir_categoria_com_dados_invalidos(): void
    {
        // Nome vazio
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/category', [
            'name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Ordem negativa
        $responseOrder = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/category', [
            'name' => 'Válida',
            'order' => -5,
        ]);

        $responseOrder->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }

    #[Test]
    public function nao_pode_inserir_categoria_com_nome_duplicado_no_mesmo_tenant(): void
    {
        Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pizzas',
            'status' => 'A',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/category', [
            'name' => 'Pizzas',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function pode_inserir_categoria_com_mesmo_nome_em_tenants_diferentes(): void
    {
        $otherTenant = Tenant::factory()->create();
        Category::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Pizzas',
            'status' => 'A',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/category', [
            'name' => 'Pizzas',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', [
            'name' => 'Pizzas',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // ==========================================
    // TESTES DE ALTERAÇÃO (PUT /api/category/{id})
    // ==========================================

    #[Test]
    public function pode_alterar_categoria_usando_id_numerico(): void
    {
        $category = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Nome Antigo',
            'description' => 'Descrição antiga',
            'order' => 1,
            'status' => 'A',
        ]);

        $updatePayload = [
            'name' => 'Nome Novo',
            'description' => 'Descrição nova atualizada',
            'order' => 10,
            'isActive' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/category/{$category->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Nome Novo',
                    'description' => 'Descrição nova atualizada',
                    'order' => 10,
                    'status' => 'A',
                ],
                'message' => 'Categoria atualizada com sucesso',
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Nome Novo',
            'description' => 'Descrição nova atualizada',
            'order' => 10,
            'status' => 'A',
        ]);
    }

    #[Test]
    public function pode_alterar_categoria_usando_uuid(): void
    {
        $category = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Bebidas Quentes',
            'description' => 'Cafés e chás',
            'order' => 2,
            'status' => 'A',
        ]);

        $updatePayload = [
            'name' => 'Bebidas Quentes & Cafés',
            'description' => 'Cafés espressos, cappuccinos e chás',
            'order' => 7,
            'status' => 'I',
            'isActive' => false,
        ];

        // O frontend envia a UUID da categoria na rota PUT /api/category/{uuid}
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/category/{$category->uuid}", $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'identify' => $category->uuid,
                    'name' => 'Bebidas Quentes & Cafés',
                    'order' => 7,
                    'status' => 'I',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'uuid' => $category->uuid,
            'name' => 'Bebidas Quentes & Cafés',
            'order' => 7,
            'status' => 'I',
            'is_active' => 0,
        ]);
    }

    #[Test]
    public function pode_alterar_categoria_mantendo_o_mesmo_nome(): void
    {
        $category = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sobremesas Geladas',
            'description' => 'Sorvetes e açaí',
            'order' => 4,
            'status' => 'A',
        ]);

        $updatePayload = [
            'name' => 'Sobremesas Geladas', // mesmo nome
            'description' => 'Descrição editada sem mudar o nome',
            'order' => 8,
            'isActive' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/category/{$category->uuid}", $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Sobremesas Geladas',
                    'description' => 'Descrição editada sem mudar o nome',
                    'order' => 8,
                ],
            ]);
    }

    #[Test]
    public function nao_pode_alterar_categoria_para_nome_de_outra_categoria_ativa(): void
    {
        Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Categoria Existente',
            'status' => 'A',
        ]);

        $categoryToEdit = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Outra Categoria',
            'status' => 'A',
        ]);

        $updatePayload = [
            'name' => 'Categoria Existente', // tenta usar nome de outra categoria
            'order' => 1,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/category/{$categoryToEdit->uuid}", $updatePayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function alterar_categoria_inexistente_retorna_404(): void
    {
        $fakeUuid = '00000000-0000-0000-0000-000000000000';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/category/{$fakeUuid}", [
            'name' => 'Nome Qualquer',
            'order' => 1,
        ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function nao_pode_alterar_categoria_de_outro_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCategory = Category::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Categoria Outro Tenant',
            'status' => 'A',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/category/{$otherCategory->uuid}", [
            'name' => 'Tentativa de Hack',
            'order' => 99,
        ]);

        // Não deve encontrar a categoria pertencente a outro tenant
        $response->assertStatus(404);
    }

    #[Test]
    public function nao_pode_alterar_categoria_sem_autenticacao(): void
    {
        $category = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->putJson("/api/category/{$category->uuid}", [
            'name' => 'Sem Token',
        ]);

        $response->assertStatus(401);
    }

    // ==========================================
    // TESTES DE REORDENAÇÃO (POST /api/category/reorder)
    // ==========================================

    #[Test]
    public function pode_reordenar_lista_de_categorias(): void
    {
        $catA = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Categoria A',
            'order' => 1,
        ]);
        $catB = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Categoria B',
            'order' => 2,
        ]);
        $catC = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Categoria C',
            'order' => 3,
        ]);

        // Reordenar para C, A, B
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/category/reorder', [
            'order' => [$catC->uuid, $catA->uuid, $catB->uuid],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ordem das categorias atualizada com sucesso',
            ]);

        $this->assertEquals(1, $catC->fresh()->order);
        $this->assertEquals(2, $catA->fresh()->order);
        $this->assertEquals(3, $catB->fresh()->order);
    }
}
