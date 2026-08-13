<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantBankAccount;
use App\Models\Bank;
use App\Models\BankAccountLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class BankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = Tenant::factory()->create();

        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->grantFullAccess($this->user, $this->tenant);

        // Gerar token
        $this->token = JWTAuth::fromUser($this->user);

        // Popular bancos
        Bank::factory()->create([
            'code' => '001',
            'name' => 'Banco do Brasil',
        ]);
    }

    #[Test]
    public function it_can_list_bank_accounts()
    {
        TenantBankAccount::factory()
            ->count(3)
            ->forTenant($this->tenant)
            ->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/bank-accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'uuid',
                        'account_type',
                        'bank_name',
                        'agency',
                        'account_number_masked',
                        'is_primary',
                        'is_verified',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_create_a_bank_account()
    {
        $data = [
            'account_type' => 'checking',
            'bank_code' => '001',
            'agency' => '1234',
            'agency_digit' => '5',
            'account_number' => '12345678',
            'account_digit' => '9',
            'account_holder_name' => 'João Silva',
            'account_holder_document' => '12345678901',
            'account_holder_type' => 'individual',
            'pix_key_type' => 'cpf',
            'pix_key' => '12345678901',
            'is_primary' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bank-accounts', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Conta bancária cadastrada com sucesso.',
            ])
            ->assertJsonStructure([
                'data' => ['uuid'],
            ]);

        $this->assertDatabaseHas('tenant_bank_accounts', [
            'tenant_id' => $this->tenant->id,
            'account_type' => 'checking',
            'bank_code' => '001',
            'agency' => '1234',
            'is_primary' => true,
        ]);

        // Verificar log
        $this->assertDatabaseHas('bank_account_logs', [
            'action' => 'created',
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bank-accounts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'account_type',
                'bank_code',
                'agency',
                'account_number',
                'account_digit',
                'account_holder_name',
                'account_holder_document',
                'account_holder_type',
            ]);
    }

    #[Test]
    public function it_can_show_a_bank_account()
    {
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/bank-accounts/' . $account->uuid);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $account->uuid,
                    'account_type' => $account->account_type,
                    'bank_name' => $account->bank_name,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'account_number', // Mostra descriptografado
                    'account_holder_document',
                ],
            ]);
    }

    #[Test]
    public function it_cannot_show_account_from_another_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $account = TenantBankAccount::factory()
            ->forTenant($otherTenant)
            ->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/bank-accounts/' . $account->uuid);

        $response->assertStatus(404);
    }

    #[Test]
    public function it_can_update_a_bank_account()
    {
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->create();

        $data = [
            'pix_key_type' => 'email',
            'pix_key' => 'novo@email.com',
            'notes' => 'Conta atualizada',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/bank-accounts/' . $account->uuid, $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Conta bancária atualizada com sucesso.',
            ]);

        $account->refresh();
        $this->assertEquals('email', $account->pix_key_type);
        $this->assertEquals('novo@email.com', $account->pix_key);
        $this->assertEquals('Conta atualizada', $account->notes);

        // Verificar log
        $this->assertDatabaseHas('bank_account_logs', [
            'action' => 'updated',
            'bank_account_id' => $account->id,
        ]);
    }

    #[Test]
    public function it_can_delete_a_bank_account()
    {
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->create(['is_primary' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/bank-accounts/' . $account->uuid);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Conta bancária excluída com sucesso.',
            ]);

        $this->assertSoftDeleted('tenant_bank_accounts', [
            'id' => $account->id,
        ]);

        // Verificar log
        $this->assertDatabaseHas('bank_account_logs', [
            'action' => 'deleted',
            'bank_account_id' => $account->id,
        ]);
    }

    #[Test]
    public function it_cannot_delete_primary_account()
    {
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->primary()
            ->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/bank-accounts/' . $account->uuid);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Não é possível excluir a conta principal. Defina outra conta como principal primeiro.',
            ]);

        $this->assertDatabaseHas('tenant_bank_accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function it_can_set_an_account_as_primary()
    {
        $account1 = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->primary()
            ->create();

        $account2 = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->create(['is_primary' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bank-accounts/' . $account2->uuid . '/set-primary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Conta definida como principal.',
            ]);

        $account1->refresh();
        $account2->refresh();

        $this->assertFalse($account1->is_primary);
        $this->assertTrue($account2->is_primary);

        // Verificar log
        $this->assertDatabaseHas('bank_account_logs', [
            'action' => 'set_primary',
            'bank_account_id' => $account2->id,
        ]);
    }

    #[Test]
    public function it_cannot_set_inactive_account_as_primary()
    {
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->inactive()
            ->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bank-accounts/' . $account->uuid . '/set-primary');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Não é possível definir uma conta inativa como principal.',
            ]);
    }

    #[Test]
    public function it_can_verify_an_account()
    {
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->unverified()
            ->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bank-accounts/' . $account->uuid . '/verify', [
            'verification_method' => 'manual',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Conta verificada com sucesso.',
            ]);

        $account->refresh();
        $this->assertTrue($account->is_verified);
        $this->assertNotNull($account->verified_at);
        $this->assertEquals('manual', $account->verification_method);

        // Verificar log
        $this->assertDatabaseHas('bank_account_logs', [
            'action' => 'verified',
            'bank_account_id' => $account->id,
        ]);
    }

    #[Test]
    public function it_can_list_available_banks()
    {
        Bank::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/bank-accounts/banks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'code',
                        'name',
                        'full_name',
                        'supports_pix',
                    ],
                ],
            ])
            ->assertJsonCount(6, 'data'); // 5 + 1 do setUp
    }

    #[Test]
    public function it_can_view_account_logs()
    {
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->create();

        // Criar alguns logs
        BankAccountLog::factory()->count(3)->create([
            'bank_account_id' => $account->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/bank-accounts/' . $account->uuid . '/logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'action',
                        'user',
                        'ip_address',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_encrypts_sensitive_data()
    {
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->create([
                'account_number' => '12345678',
                'account_holder_document' => '12345678901',
            ]);

        // Verificar que dados NÃO estão em plain text no banco
        $raw = DB::table('tenant_bank_accounts')
            ->where('id', $account->id)
            ->first();

        $this->assertNotEquals('12345678', $raw->account_number);
        $this->assertNotEquals('12345678901', $raw->account_holder_document);

        // Verificar que model descriptografa corretamente
        $this->assertEquals('12345678', $account->account_number);
        $this->assertEquals('12345678901', $account->account_holder_document);
    }

    #[Test]
    public function it_generates_masked_account_number()
    {
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->create([
                'account_number' => '12345678',
                'account_digit' => '9',
            ]);

        $masked = $account->masked_account_number;

        $this->assertStringStartsWith('12', $masked);
        $this->assertStringEndsWith('78-9', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    #[Test]
    public function it_generates_masked_document()
    {
        // CPF
        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->create([
                'account_holder_document' => '12345678901',
            ]);

        $masked = $account->masked_document;
        $this->assertStringStartsWith('123', $masked);
        $this->assertStringEndsWith('01', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/bank-accounts');
        $response->assertStatus(401);
    }

    #[Test]
    public function only_tenant_users_can_access_their_accounts()
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $this->grantFullAccess($otherUser, $otherTenant);
        $otherToken = JWTAuth::fromUser($otherUser);

        $account = TenantBankAccount::factory()
            ->forTenant($this->tenant)
            ->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherToken,
        ])->getJson('/api/bank-accounts/' . $account->uuid);

        $response->assertStatus(404);
    }
}

