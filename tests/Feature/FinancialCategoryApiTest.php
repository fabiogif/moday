<?php

namespace Tests\Feature;

use App\Models\FinancialCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use App\Http\Middleware\Authenticate as AppAuthenticate;
use Illuminate\Auth\Middleware\Authenticate as LaravelAuthenticate;

class FinancialCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private array $headers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->grantFullAccess($this->user, $this->tenant);
        $this->withoutMiddleware([
            JWTAuthenticate::class,
            AppAuthenticate::class,
            LaravelAuthenticate::class,
        ]);
        $this->actingAs($this->user);
    }

    private function getAuthHeaders(): array
    {
        return $this->headers;
    }

    #[Test]
    public function it_can_list_all_categories()
    {
        FinancialCategory::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'receita',
        ]);

        FinancialCategory::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'despesa',
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/financial-categories');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function it_can_filter_categories_by_type()
    {
        FinancialCategory::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'receita',
            'applies_to_receivable' => true,
            'applies_to_payable' => false,
            'is_active' => true,
        ]);

        FinancialCategory::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'despesa',
            'applies_to_receivable' => false,
            'applies_to_payable' => true,
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/financial-categories?type=receita');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $category) {
            $this->assertTrue($category['applies_to_receivable']);
        }
    }

    #[Test]
    public function it_can_create_a_category()
    {
        $categoryData = [
            'name' => 'Vendas',
            'type' => 'receita',
            'description' => 'Receitas de vendas',
            'color' => '#10b981',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/financial-categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Vendas',
                    'type' => 'receita',
                ]
            ]);

        $this->assertDatabaseHas('financial_categories', [
            'name' => 'Vendas',
            'type' => 'receita',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/financial-categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'applies_to_receivable']);
    }

    #[Test]
    public function it_validates_type_must_be_receita_or_despesa()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/financial-categories', [
            'name' => 'Teste',
            'type' => 'invalido',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function it_validates_color_format()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/financial-categories', [
            'name' => 'Teste',
            'type' => 'receita',
            'color' => 'azul', // Formato inválido
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    }

    #[Test]
    public function it_prevents_duplicate_name_within_same_type()
    {
        FinancialCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Vendas',
            'type' => 'receita',
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/financial-categories', [
            'name' => 'Vendas',
            'type' => 'receita',
        ]);

        $response->assertStatus(500); // Service lança exceção
    }

    #[Test]
    public function it_allows_same_name_for_different_types()
    {
        FinancialCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Outros',
            'type' => 'receita',
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/financial-categories', [
            'name' => 'Outros',
            'type' => 'despesa', // Tipo diferente - deve permitir
        ]);

        $response->assertStatus(201);
    }

    #[Test]
    public function it_can_update_a_category()
    {
        $category = FinancialCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Nome Original',
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->putJson("/api/financial-categories/{$category->uuid}", [
            'name' => 'Nome Atualizado',
            'applies_to_payable' => true,
            'applies_to_receivable' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Nome Atualizado',
                ]
            ]);
    }

    #[Test]
    public function it_can_delete_a_category_without_relations()
    {
        $category = FinancialCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->deleteJson("/api/financial-categories/{$category->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('financial_categories', ['id' => $category->id]);
    }

    #[Test]
    public function it_prevents_deleting_category_with_expenses()
    {
        $category = FinancialCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        \App\Models\Expense::factory()->create([
            'tenant_id' => $this->tenant->id,
            'financial_category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('expenses', [
            'financial_category_id' => $category->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertSame(1, \App\Models\FinancialCategory::find($category->id)->expenses()->count());

        $response = $this->withHeaders($this->getAuthHeaders())->deleteJson("/api/financial-categories/{$category->uuid}");

        $response->assertStatus(500);
        $this->assertDatabaseHas('financial_categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_category()
    {
        $response = $this->getJson('/api/financial-categories/non-existent-uuid');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_prevents_accessing_category_from_another_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $otherCategory = FinancialCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson("/api/financial-categories/{$otherCategory->uuid}");

        $response->assertStatus(404);
    }
}

