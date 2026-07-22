<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Client;
use App\Models\FinancialCategory;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AccountReceivableApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private FinancialCategory $category;
    private Client $client;
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
            'type' => 'receita',
        ]);

        $this->client = Client::factory()->create([
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
    public function it_can_list_accounts_receivable()
    {
        AccountReceivable::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/accounts-receivable');

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
    public function it_can_create_an_account_receivable()
    {
        $accountReceivableData = [
            'description' => 'Venda de Produto - Janeiro 2025',
            'financial_category_id' => $this->category->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->uuid,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'amount' => 2500.00,
            'status' => 'pendente',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-receivable', $accountReceivableData);

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

        $this->assertDatabaseHas('accounts_receivable', [
            'description' => 'Venda de Produto - Janeiro 2025',
            'amount' => 2500.00,
            'status' => 'pendente',
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-receivable', []);

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
        $accountReceivableData = [
            'description' => 'Teste',
            'issue_date' => '2025-01-10',
            'due_date' => '2025-01-05', // Anterior à data de emissão
            'amount' => 100.00,
            'status' => 'pendente',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-receivable', $accountReceivableData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    #[Test]
    public function it_validates_amount_must_be_positive()
    {
        $accountReceivableData = [
            'description' => 'Teste',
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-10',
            'amount' => -100.00, // Valor negativo
            'status' => 'pendente',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-receivable', $accountReceivableData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function it_can_filter_accounts_receivable_by_status()
    {
        AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'due_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'recebido',
        ]);

        AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'due_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/accounts-receivable?status=pendente');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $account) {
            $this->assertEquals('pendente', $account['status']);
        }
    }

    #[Test]
    public function it_can_filter_accounts_receivable_by_client()
    {
        $client1 = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $client2 = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        AccountReceivable::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client1->id,
        ]);

        AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client2->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/accounts-receivable?client_id={$client1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_can_update_an_account_receivable()
    {
        $accountReceivable = AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Descrição Original',
            'amount' => 1000.00,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson("/api/accounts-receivable/{$accountReceivable->uuid}", [
                'description' => 'Descrição Atualizada',
                'amount' => 1200.00,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('accounts_receivable', [
            'id' => $accountReceivable->id,
            'description' => 'Descrição Atualizada',
            'amount' => 1200.00,
        ]);
    }

    #[Test]
    public function it_can_delete_an_account_receivable()
    {
        $accountReceivable = AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->deleteJson("/api/accounts-receivable/{$accountReceivable->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('accounts_receivable', ['id' => $accountReceivable->id]);
    }

    #[Test]
    public function it_can_mark_account_receivable_as_received()
    {
        $accountReceivable = AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'amount' => 1000.00,
        ]);

        $receiptData = [
            'receipt_date' => '2025-01-15',
            'amount_received' => 1000.00,
            'payment_method_id' => $this->paymentMethod->uuid,
            'notes' => 'Recebimento via PIX',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/accounts-receivable/{$accountReceivable->uuid}/receive", $receiptData);

        $response->assertStatus(200);

        $accountReceivable->refresh();
        
        $this->assertEquals('recebido', $accountReceivable->status);
        $this->assertEquals(1000.00, (float) $accountReceivable->amount_received);
        $this->assertEquals('2025-01-15', $accountReceivable->receipt_date?->format('Y-m-d'));
    }

    #[Test]
    public function it_can_register_partial_receipt()
    {
        $accountReceivable = AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'amount' => 1000.00,
        ]);

        $receiptData = [
            'receipt_date' => '2025-01-15',
            'amount_received' => 600.00, // Recebimento parcial
            'payment_method_id' => $this->paymentMethod->uuid,
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/accounts-receivable/{$accountReceivable->uuid}/receive", $receiptData);

        $response->assertStatus(200);

        $accountReceivable->refresh();
        
        $this->assertEquals('parcial', $accountReceivable->status);
        $this->assertEquals(600.00, (float) $accountReceivable->amount_received);
    }

    #[Test]
    public function it_can_get_accounts_receivable_from_order()
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar 3 contas a receber (parcelas) vinculadas ao pedido
        AccountReceivable::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
        ]);

        // Criar conta a receber de outro pedido
        AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'order_id' => null,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/accounts-receivable/from-order/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $account) {
            $this->assertEquals($order->id, $account['order_id']);
        }
    }

    #[Test]
    public function it_can_get_stats()
    {
        AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pendente',
            'amount' => 1000.00,
        ]);

        AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'recebido',
            'amount' => 500.00,
        ]);

        AccountReceivable::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'vencido',
            'amount' => 300.00,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/accounts-receivable/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_pending',
                    'total_received',
                    'total_overdue',
                ],
            ]);
    }

    #[Test]
    public function it_prevents_accessing_account_receivable_from_another_tenant()
    {
        $plan = \App\Models\Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $otherAccountReceivable = AccountReceivable::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/accounts-receivable/{$otherAccountReceivable->uuid}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_validates_status_field()
    {
        $accountReceivableData = [
            'description' => 'Teste',
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-10',
            'amount' => 100.00,
            'status' => 'invalido', // Status inválido
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-receivable', $accountReceivableData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function it_can_create_account_receivable_with_installments()
    {
        $accountReceivableData = [
            'description' => 'Venda parcelada',
            'financial_category_id' => $this->category->id,
            'issue_date' => '2025-01-01',
            'due_date' => '2025-02-01',
            'amount' => 500.00,
            'status' => 'pendente',
            'installment_number' => 1,
            'total_installments' => 5,
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-receivable', $accountReceivableData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('accounts_receivable', [
            'description' => 'Venda parcelada',
            'installment_number' => 1,
            'total_installments' => 5,
        ]);
    }

    #[Test]
    public function it_can_link_account_receivable_to_order()
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $accountReceivableData = [
            'description' => 'Pedido #' . $order->id,
            'financial_category_id' => $this->category->id,
            'order_id' => $order->id,
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-10',
            'amount' => 1500.00,
            'status' => 'pendente',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-receivable', $accountReceivableData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('accounts_receivable', [
            'order_id' => $order->id,
            'description' => 'Pedido #' . $order->id,
        ]);
    }

    #[Test]
    public function it_can_create_account_receivable_with_discount()
    {
        $accountReceivableData = [
            'description' => 'Venda com desconto',
            'financial_category_id' => $this->category->id,
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-10',
            'amount' => 1000.00,
            'discount' => 100.00,
            'status' => 'pendente',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-receivable', $accountReceivableData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('accounts_receivable', [
            'amount' => 1000.00,
            'discount' => 100.00,
        ]);
    }

    #[Test]
    public function it_can_create_account_receivable_with_interest_and_fine()
    {
        $accountReceivableData = [
            'description' => 'Conta com juros e multa',
            'financial_category_id' => $this->category->id,
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-10',
            'amount' => 1000.00,
            'interest' => 50.00,
            'fine' => 30.00,
            'status' => 'vencido',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/accounts-receivable', $accountReceivableData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('accounts_receivable', [
            'amount' => 1000.00,
            'interest' => 50.00,
            'fine' => 30.00,
        ]);
    }
}

