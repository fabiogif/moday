<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\DetailsPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant e usuário autenticado
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $this->token = auth('api')->login($this->user);
        $this->defaultHeaders['Authorization'] = 'Bearer ' . $this->token;
    }

    /**
     * Teste: Criar um novo plano com detalhes
     */
    public function test_can_create_plan_with_details()
    {
        $planData = [
            'name' => 'Plano Teste',
            'url' => 'plano-teste',
            'price' => 99.90,
            'description' => 'Plano criado para testes',
            'is_active' => true,
            'max_users' => 10,
            'max_products' => 200,
            'max_orders_per_month' => 500,
            'has_marketing' => true,
            'has_reports' => true,
            'details' => [
                ['name' => 'Feature 1'],
                ['name' => 'Feature 2'],
                ['name' => 'Feature 3'],
            ],
        ];

        $response = $this->postJson('/api/plan', $planData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'url',
                         'price',
                         'description',
                         'is_active',
                         'max_users',
                         'max_products',
                         'max_orders_per_month',
                         'has_marketing',
                         'has_reports',
                         'details',
                     ],
                 ]);

        $this->assertDatabaseHas('plans', [
            'name' => 'Plano Teste',
            'url' => 'plano-teste',
            'price' => 99.90,
            'has_marketing' => true,
            'has_reports' => true,
        ]);

        $plan = Plan::where('name', 'Plano Teste')->first();
        $this->assertNotNull($plan);
        $this->assertCount(3, $plan->details);
    }

    /**
     * Teste: Criar plano sem campos obrigatórios deve falhar
     */
    public function test_cannot_create_plan_without_required_fields()
    {
        $response = $this->postJson('/api/plan', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'url', 'price']);
    }

    /**
     * Teste: Listar todos os planos
     */
    public function test_can_list_all_plans()
    {
        // Criar 3 planos
        Plan::factory()->count(3)->create();

        $response = $this->getJson('/api/plan');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'url',
                             'price',
                             'description',
                             'is_active',
                             'details',
                         ],
                     ],
                 ]);

        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /**
     * Teste: Visualizar um plano específico
     */
    public function test_can_show_specific_plan()
    {
        $plan = Plan::factory()->create([
            'name' => 'Plano Premium',
            'price' => 199.90,
        ]);

        DetailsPlan::factory()->count(5)->create([
            'plan_id' => $plan->id,
        ]);

        $response = $this->getJson("/api/plan/{$plan->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $plan->id,
                         'name' => 'Plano Premium',
                         'price' => '199.90',
                     ],
                 ]);

        $this->assertCount(5, $response->json('data.details'));
    }

    /**
     * Teste: Visualizar plano inexistente deve retornar 404
     */
    public function test_show_nonexistent_plan_returns_404()
    {
        $response = $this->getJson('/api/plan/99999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Plano não encontrado',
                 ]);
    }

    /**
     * Teste: Atualizar um plano existente
     */
    public function test_can_update_existing_plan()
    {
        $plan = Plan::factory()->create([
            'name' => 'Plano Original',
            'price' => 49.90,
            'has_marketing' => false,
            'has_reports' => false,
        ]);

        $updateData = [
            'name' => 'Plano Atualizado',
            'url' => 'plano-atualizado',
            'price' => 79.90,
            'description' => 'Descrição atualizada',
            'has_marketing' => true,
            'has_reports' => true,
            'max_users' => 20,
            'max_products' => 300,
        ];

        $response = $this->putJson("/api/plan/{$plan->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Plano atualizado com sucesso',
                 ])
                 ->assertJsonPath('data.name', 'Plano Atualizado');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Plano Atualizado',
            'price' => 79.90,
            'has_marketing' => true,
            'has_reports' => true,
        ]);
    }

    /**
     * Teste: Atualizar plano com detalhes
     */
    public function test_can_update_plan_with_details()
    {
        $plan = Plan::factory()->create([
            'name' => 'Plano com Detalhes',
        ]);

        // Criar detalhes iniciais
        DetailsPlan::factory()->count(2)->create([
            'plan_id' => $plan->id,
        ]);

        $updateData = [
            'name' => 'Plano com Detalhes Atualizados',
            'url' => $plan->url,
            'price' => $plan->price,
            'details' => [
                ['name' => 'Nova Feature 1'],
                ['name' => 'Nova Feature 2'],
                ['name' => 'Nova Feature 3'],
            ],
        ];

        $response = $this->putJson("/api/plan/{$plan->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Plano com Detalhes Atualizados');

        $plan->refresh();
        $this->assertEquals('Plano com Detalhes Atualizados', $plan->name);
        
        // Verificar se os novos detalhes foram criados
        $this->assertGreaterThanOrEqual(3, $plan->details()->count());
    }

    /**
     * Teste: Excluir um plano
     */
    public function test_can_delete_plan()
    {
        $plan = Plan::factory()->create([
            'name' => 'Plano a Deletar',
        ]);

        DetailsPlan::factory()->count(3)->create([
            'plan_id' => $plan->id,
        ]);

        $planId = $plan->id;

        $response = $this->deleteJson("/api/plan/{$planId}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Plano deletado com sucesso',
                 ]);

        $this->assertDatabaseMissing('plans', [
            'id' => $planId,
        ]);

        // Verificar se os detalhes também foram deletados (cascade)
        $this->assertEquals(0, DetailsPlan::where('plan_id', $planId)->count());
    }

    /**
     * Teste: Excluir plano inexistente deve retornar erro
     */
    public function test_delete_nonexistent_plan_fails()
    {
        $response = $this->deleteJson('/api/plan/99999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Plano não encontrado',
                 ]);
    }

    /**
     * Teste: Criar plano com URL duplicada deve falhar
     */
    public function test_cannot_create_plan_with_duplicate_url()
    {
        Plan::factory()->create([
            'url' => 'plano-unico',
        ]);

        $response = $this->postJson('/api/plan', [
            'name' => 'Outro Plano',
            'url' => 'plano-unico', // URL duplicada
            'price' => 99.90,
            'description' => 'Tentativa de duplicação',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['url']);
    }

    /**
     * Teste: Validar valores numéricos nos campos de limites
     */
    public function test_plan_limits_must_be_numeric()
    {
        $response = $this->postJson('/api/plan', [
            'name' => 'Plano Teste',
            'url' => 'plano-teste',
            'price' => 'não é número', // Inválido
            'max_users' => 'não é número', // Inválido
            'max_products' => 'abc', // Inválido
            'max_orders_per_month' => 'xyz', // Inválido
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['price', 'max_users', 'max_products', 'max_orders_per_month']);
    }

    /**
     * Teste: Criar plano "Grátis" com preço zero
     */
    public function test_can_create_free_plan()
    {
        $response = $this->postJson('/api/plan', [
            'name' => 'Grátis',
            'url' => 'gratis',
            'price' => 0.00,
            'description' => 'Plano gratuito',
            'max_users' => 1,
            'max_products' => 50,
            'max_orders_per_month' => 30,
            'has_marketing' => false,
            'has_reports' => false,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('plans', [
            'name' => 'Grátis',
            'price' => 0.00,
        ]);
    }

    /**
     * Teste: Criar plano "Premium" com recursos ilimitados (null)
     */
    public function test_can_create_premium_plan_with_unlimited_resources()
    {
        $response = $this->postJson('/api/plan', [
            'name' => 'Premium',
            'url' => 'premium',
            'price' => 199.90,
            'description' => 'Plano premium ilimitado',
            'max_users' => 999999,
            'max_products' => 999999,
            'max_orders_per_month' => null, // Ilimitado
            'has_marketing' => true,
            'has_reports' => true,
        ]);

        $response->assertStatus(201);

        $plan = Plan::where('name', 'Premium')->first();
        $this->assertNull($plan->max_orders_per_month);
    }

    /**
     * Teste: Listar detalhes de um plano específico
     */
    public function test_can_list_plan_details()
    {
        $plan = Plan::factory()->create();
        
        $details = [
            DetailsPlan::factory()->create([
                'plan_id' => $plan->id,
                'name' => 'Recurso 1',
            ]),
            DetailsPlan::factory()->create([
                'plan_id' => $plan->id,
                'name' => 'Recurso 2',
            ]),
            DetailsPlan::factory()->create([
                'plan_id' => $plan->id,
                'name' => 'Recurso 3',
            ]),
        ];

        $response = $this->getJson("/api/plan/{$plan->id}/details");

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'plan_id',
                         ],
                     ],
                 ]);
    }
}

