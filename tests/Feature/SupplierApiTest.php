<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SupplierApiTest extends TestCase
{
    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant e usuário para testes
        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Gerar token JWT
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function getAuthHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function it_can_list_suppliers_for_authenticated_tenant()
    {
        // Criar fornecedores para o tenant
        Supplier::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar fornecedor de outro tenant (não deve aparecer)
        $otherTenant = Tenant::factory()->create();
        Supplier::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/suppliers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'name',
                        'document',
                        'document_type',
                        'phone',
                        'is_active',
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data'); // Apenas os 3 do tenant
    }

    #[Test]
    public function it_can_create_a_supplier()
    {
        $supplierData = [
            'name' => 'Fornecedor Teste Ltda',
            'fantasy_name' => 'Fornecedor Teste',
            'document' => '11222333000181',
            'document_type' => 'cnpj',
            'email' => 'contato@fornecedor.com',
            'phone' => '11987654321',
            'is_active' => true,
        ];

        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/suppliers', $supplierData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'document',
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Fornecedor Teste Ltda',
                    'document' => '11222333000181',
                ]
            ]);

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Fornecedor Teste Ltda',
            'document' => '11222333000181',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_supplier()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/suppliers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    #[Test]
    public function it_prevents_duplicate_document()
    {
        // Criar fornecedor
        Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
            'document' => '12345678901234',
        ]);

        // Tentar criar outro com mesmo documento
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/suppliers', [
            'name' => 'Outro Fornecedor',
            'document' => '12345678901234',
            'document_type' => 'cnpj',
            'phone' => '11987654321',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document']);
    }

    #[Test]
    public function it_can_update_a_supplier()
    {
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Nome Original',
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->putJson("/api/suppliers/{$supplier->uuid}", [
            'name' => 'Nome Atualizado',
            'phone' => '11999999999',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Nome Atualizado',
                ]
            ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Nome Atualizado',
        ]);
    }

    #[Test]
    public function it_can_delete_a_supplier_without_expenses()
    {
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->deleteJson("/api/suppliers/{$supplier->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    #[Test]
    public function it_prevents_deletion_of_supplier_with_expenses()
    {
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar despesa vinculada
        \App\Models\Expense::factory()->create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $supplier->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->deleteJson("/api/suppliers/{$supplier->uuid}");

        $response->assertStatus(400); // Não pode excluir por ter vínculos
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_can_check_if_document_exists()
    {
        Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
            'document' => '12345678901234',
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/suppliers/check-document?document=12345678901234');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exists' => true,
                ]
            ]);
    }

    #[Test]
    public function it_returns_404_when_supplier_not_found()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/suppliers/non-existent-uuid');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_prevents_accessing_supplier_from_another_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $otherSupplier = Supplier::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson("/api/suppliers/{$otherSupplier->uuid}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_validates_email_format()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/suppliers', [
            'name' => 'Fornecedor Teste',
            'document' => '12345678901234',
            'document_type' => 'cnpj',
            'phone' => '11987654321',
            'email' => 'email-invalido',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_validates_state_must_be_2_characters()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/suppliers', [
            'name' => 'Fornecedor Teste',
            'document' => '12345678901234',
            'document_type' => 'cnpj',
            'phone' => '11987654321',
            'state' => 'SAO', // 3 caracteres - inválido
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['state']);
    }
}

