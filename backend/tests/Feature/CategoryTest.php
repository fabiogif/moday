<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CategoryTest extends TestCase
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

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'name',
                        'identify',
                        'description',
                        'url',
                        'status',
                        'created_at'
                    ],
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'name' => 'Churrasco',
                        'description' => 'Categoria para produtos de churrasco',
                        'url' => 'churrasco'
                    ]
                ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Churrasco',
            'description' => 'Categoria para produtos de churrasco',
            'url' => 'churrasco',
            'tenant_id' => $this->tenant->id
        ]);
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

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
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

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'name',
                            'identify',
                            'description',
                            'url',
                            'status',
                            'created_at'
                        ]
                    ],
                    'meta' => [
                        'total',
                        'current_page',
                        'per_page'
                    ]
                ])
                ->assertJson([
                    'success' => true
                ]);

        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function pode_buscar_categoria_por_identify()
    {
        $category = Category::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson("/api/category/{$category->uuid}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'name',
                        'identify',
                        'description',
                        'url',
                        'status',
                        'created_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'identify' => $category->uuid
                    ]
                ]);
    }

    #[Test]
    public function pode_atualizar_categoria()
    {
        $category = Category::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $updateData = [
            'name' => 'Churrasco Atualizado',
            'description' => 'Descrição atualizada',
            'url' => 'churrasco-atualizado'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson("/api/category/{$category->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'name',
                        'identify',
                        'description',
                        'url',
                        'status',
                        'created_at'
                    ],
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'name' => 'Churrasco Atualizado',
                        'description' => 'Descrição atualizada',
                        'url' => 'churrasco-atualizado'
                    ]
                ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Churrasco Atualizado',
            'description' => 'Descrição atualizada',
            'url' => 'churrasco-atualizado'
        ]);
    }

    #[Test]
    public function pode_deletar_categoria()
    {
        $category = Category::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->deleteJson("/api/category/{$category->uuid}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Categoria deletada com sucesso'
                ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id
        ]);
    }

    #[Test]
    public function nao_pode_acessar_categoria_de_outro_tenant()
    {
        // Criar outro tenant e categoria
        $otherTenant = Tenant::factory()->create();
        $otherCategory = Category::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson("/api/category/{$otherCategory->uuid}");

        $response->assertStatus(404);
    }

    #[Test]
    public function valida_unicidade_do_nome_da_categoria_por_tenant()
    {
        // Criar categoria existente
        Category::factory()->create([
            'name' => 'Churrasco',
            'tenant_id' => $this->tenant->id
        ]);

        $categoryData = [
            'name' => 'Churrasco', // Mesmo nome
            'description' => 'Nova categoria'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/category', $categoryData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function pode_criar_categoria_com_mesmo_nome_em_tenants_diferentes()
    {
        // Criar outro tenant
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);
        $otherToken = JWTAuth::fromUser($otherUser);

        // Criar categoria no primeiro tenant
        Category::factory()->create([
            'name' => 'Churrasco',
            'tenant_id' => $this->tenant->id
        ]);

        // Criar categoria com mesmo nome no segundo tenant
        $categoryData = [
            'name' => 'Churrasco',
            'description' => 'Categoria do segundo tenant'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherToken,
            'Accept' => 'application/json'
        ])->postJson('/api/category', $categoryData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'name' => 'Churrasco'
                    ]
                ]);

        // Verificar que ambas as categorias existem
        $this->assertDatabaseHas('categories', [
            'name' => 'Churrasco',
            'tenant_id' => $this->tenant->id
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Churrasco',
            'tenant_id' => $otherTenant->id
        ]);
    }
}
