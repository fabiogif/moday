<?php

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\FinancialCategory;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AccountPayableApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private FinancialCategory $category;
    private Supplier $supplier;
    private PaymentMethod $paymentMethod;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->grantFullAccess($this->user, $this->tenant);

        $this->category = FinancialCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'despesa',
        ]);

        $this->supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    private function getAuthHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function it_can_list_accounts_payable()
    {
        AccountPayable::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/accounts-payable');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'description',
                        'amount',
                        'status',
                        'due_date',
                        'tenant_id',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_create_an_account_payable()
    {
        $accountPayableData = [
            'description' => 'Aluguel - Janeiro 2025',
            'financial_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'payment_method_id' => $this->paymentMethod->uuid,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'amount' => 1500.00,
            'status' => 'pendente',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-payable', $accountPayableData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'description',
                    'amount',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('accounts_payable', [
            'description' => 'Aluguel - Janeiro 2025',
            'amount' => 1500.00,
            'status' => 'pendente',
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-payable', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'description',
                'issue_date',
                'due_date',
                'amount',
                'status',
            ]);
    }

    #[Test]
    public function it_validates_due_date_must_be_after_or_equal_issue_date()
    {
        $accountPayableData = [
            'description' => 'Teste',
            'issue_date' => '2025-01-10',
            'due_date' => '2025-01-05', // Anterior à data de emissão
            'amount' => 100.00,
            'status' => 'pendente',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-payable', $accountPayableData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    #[Test]
    public function it_validates_amount_must_be_positive()
    {
        $accountPayableData = [
            'description' => 'Teste',
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-10',
            'amount' => -100.00, // Valor negativo
            'status' => 'pendente',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-payable', $accountPayableData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function it_can_filter_accounts_payable_by_status()
    {
        AccountPayable::factory()->pendente()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        AccountPayable::factory()->pago()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        AccountPayable::factory()->pendente()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/accounts-payable?status=pendente');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $account) {
            $this->assertEquals('pendente', $account['status']);
        }
    }

    #[Test]
    public function it_can_filter_accounts_payable_by_supplier()
    {
        $supplier1 = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
        $supplier2 = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);

        AccountPayable::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $supplier1->id,
        ]);

        AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $supplier2->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/accounts-payable?supplier_id={$supplier1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_can_update_an_account_payable()
    {
        $accountPayable = AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Descrição Original',
            'amount' => 1000.00,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson("/api/accounts-payable/{$accountPayable->uuid}", [
                'description' => 'Descrição Atualizada',
                'amount' => 1200.00,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('accounts_payable', [
            'id' => $accountPayable->id,
            'description' => 'Descrição Atualizada',
            'amount' => 1200.00,
        ]);
    }

    #[Test]
    public function it_can_delete_an_account_payable()
    {
        $accountPayable = AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->deleteJson("/api/accounts-payable/{$accountPayable->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('accounts_payable', ['id' => $accountPayable->id]);
    }

    #[Test]
    public function it_can_mark_account_payable_as_paid()
    {
        $accountPayable = AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'amount' => 1000.00,
        ]);

        $paymentData = [
            'payment_date' => '2025-01-15',
            'amount_paid' => 1000.00,
            'payment_method_id' => $this->paymentMethod->uuid,
            'notes' => 'Pagamento via transferência',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/accounts-payable/{$accountPayable->uuid}/pay", $paymentData);

        $response->assertStatus(200);

        $accountPayable->refresh();
        
        $this->assertEquals('pago', $accountPayable->status);
        $this->assertEquals(1000.00, (float) $accountPayable->amount_paid);
        $this->assertEquals('2025-01-15', $accountPayable->payment_date?->format('Y-m-d'));
    }

    #[Test]
    public function it_can_register_partial_payment()
    {
        $accountPayable = AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'amount' => 1000.00,
        ]);

        $paymentData = [
            'payment_date' => '2025-01-15',
            'amount_paid' => 500.00, // Pagamento parcial
            'payment_method_id' => $this->paymentMethod->uuid,
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/accounts-payable/{$accountPayable->uuid}/pay", $paymentData);

        $response->assertStatus(200);

        $accountPayable->refresh();
        
        $this->assertEquals('parcial', $accountPayable->status);
        $this->assertEquals(500.00, (float) $accountPayable->amount_paid);
    }

    #[Test]
    public function it_can_get_due_alerts()
    {
        // Contas que vencem nos próximos 7 dias
        AccountPayable::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        // Conta que vence em 10 dias (fora do período de alerta)
        AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'due_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        // Conta já paga (não deve aparecer)
        AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pago',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/accounts-payable/alerts?days=7');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_can_get_stats()
    {
        AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'amount' => 1000.00,
        ]);

        AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pago',
            'amount' => 500.00,
        ]);

        AccountPayable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'vencido',
            'amount' => 300.00,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/accounts-payable/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_pending',
                    'total_paid',
                    'total_overdue',
                ],
            ]);
    }

    #[Test]
    public function it_prevents_accessing_account_payable_from_another_tenant()
    {
        $plan = \App\Models\Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $otherAccountPayable = AccountPayable::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/accounts-payable/{$otherAccountPayable->uuid}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_validates_status_field()
    {
        $accountPayableData = [
            'description' => 'Teste',
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-10',
            'amount' => 100.00,
            'status' => 'invalido', // Status inválido
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-payable', $accountPayableData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function it_can_create_account_payable_with_installments()
    {
        $accountPayableData = [
            'description' => 'Compra parcelada',
            'issue_date' => '2025-01-01',
            'due_date' => '2025-02-01',
            'amount' => 300.00,
            'status' => 'pendente',
            'financial_category_id' => $this->category->id,
            'installment_number' => 1,
            'total_installments' => 3,
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-payable', $accountPayableData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('accounts_payable', [
            'description' => 'Compra parcelada',
            'installment_number' => 1,
            'total_installments' => 3,
        ]);
    }
}

