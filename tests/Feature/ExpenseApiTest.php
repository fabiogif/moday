<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\FinancialCategory;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private FinancialCategory $category;
    private Supplier $supplier;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->category = FinancialCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'despesa',
        ]);

        $this->supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    private function getAuthHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function it_can_list_expenses()
    {
        Expense::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/expenses');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_create_an_expense()
    {
        $expenseData = [
            'description' => 'Aluguel do mês',
            'financial_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'amount' => 3000.00,
            'status' => 'pendente',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/expenses', $expenseData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'description' => 'Aluguel do mês',
                    'amount' => 3000.00,
                ]
            ]);

        $this->assertDatabaseHas('expenses', [
            'description' => 'Aluguel do mês',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/expenses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'description',
                'issue_date',
                'due_date',
                'amount',
            ]);
    }

    #[Test]
    public function it_validates_due_date_must_be_after_or_equal_issue_date()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/expenses', [
            'description' => 'Teste',
            'issue_date' => '2025-12-01',
            'due_date' => '2025-11-01', // Anterior à emissão
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    #[Test]
    public function it_validates_amount_must_be_positive()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/expenses', [
            'description' => 'Teste',
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->format('Y-m-d'),
            'amount' => 0, // Zero - inválido
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function it_can_filter_expenses_by_status()
    {
        Expense::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
        ]);

        Expense::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pago',
        ]);

        $response = $this->getJson('/api/expenses?status=pendente');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_can_filter_expenses_by_category()
    {
        $category2 = FinancialCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'despesa',
        ]);

        Expense::factory()->create([
            'tenant_id' => $this->tenant->id,
            'financial_category_id' => $this->category->id,
        ]);

        Expense::factory()->create([
            'tenant_id' => $this->tenant->id,
            'financial_category_id' => $category2->id,
        ]);

        $response = $this->getJson("/api/expenses?category_id={$this->category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_can_update_an_expense()
    {
        $expense = Expense::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Original',
            'status' => 'pendente',
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->putJson("/api/expenses/{$expense->uuid}", [
            'description' => 'Atualizado',
            'status' => 'pago',
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'description' => 'Atualizado',
                    'status' => 'pago',
                ]
            ]);
    }

    #[Test]
    public function it_prevents_changing_paid_status_back_to_pending()
    {
        $expense = Expense::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pago',
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->putJson("/api/expenses/{$expense->uuid}", [
            'status' => 'pendente',
        ]);

        $response->assertStatus(500); // Service lança exceção
    }

    #[Test]
    public function it_can_delete_an_expense()
    {
        $expense = Expense::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->deleteJson("/api/expenses/{$expense->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function it_can_get_expense_stats()
    {
        Expense::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'due_date' => now(),
        ]);

        Expense::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pago',
            'due_date' => now(),
            'amount' => 1000,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/expenses/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_month',
                    'pending',
                    'paid_month',
                ]
            ]);
    }

    #[Test]
    public function it_prevents_accessing_expense_from_another_tenant()
    {
        $plan = \App\Models\Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $otherExpense = Expense::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson("/api/expenses/{$otherExpense->uuid}");

        $response->assertStatus(404);
    }
}

