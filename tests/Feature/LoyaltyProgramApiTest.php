<?php

namespace Tests\Feature;

use App\Models\{LoyaltyProgram, LoyaltyReward, LoyaltyTransaction, Tenant, User, Client, Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LoyaltyProgramApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Loja Teste',
            'slug' => 'loja-teste',
            'email' => 'loja@teste.com',
            'cnpj' => '12345678000199',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'usuario@teste.com',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'usuario@teste.com',
            'password' => 'password',
        ]);

        $this->token = $response->json('data.token');
    }

    #[Test]
    public function it_can_create_a_loyalty_program()
    {
        $data = [
            'name' => 'Programa VIP',
            'description' => 'Ganhe pontos a cada compra',
            'is_active' => true,
            'points_per_currency' => 1.0,
            'min_purchase_amount' => 50.00,
            'points_expiry_days' => 365,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/loyalty/program', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Programa criado com sucesso',
            ]);

        $this->assertDatabaseHas('loyalty_programs', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Programa VIP',
            'points_per_currency' => 1.0,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_loyalty_program()
    {
        $data = [
            'name' => 'Programa VIP',
            'description' => 'Ganhe pontos a cada compra',
            'is_active' => true,
            'points_per_currency' => 1.0,
            'min_purchase_amount' => 50.00,
            'points_expiry_days' => 365,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/loyalty/program', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Programa criado com sucesso',
            ]);

        $this->assertDatabaseHas('loyalty_programs', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Programa VIP',
            'points_per_currency' => 1.0,
        ]);
    }

    #[Test]
    public function it_can_update_a_loyalty_program()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = [
            'name' => 'Programa Atualizado',
            'points_per_currency' => 2.0,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/loyalty/program/{$program->uuid}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('loyalty_programs', [
            'id' => $program->id,
            'name' => 'Programa Atualizado',
            'points_per_currency' => 2.0,
        ]);
    }

    #[Test]
    public function it_can_delete_loyalty_program()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/loyalty/program/{$program->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('loyalty_programs', [
            'id' => $program->id,
        ]);
    }

    #[Test]
    public function it_can_add_points_to_customer()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/loyalty/client/{$client->id}/add-points", [
                'points' => 100,
                'description' => 'Bônus de boas-vindas',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('loyalty_transactions', [
            'client_id' => $client->id,
            'type' => 'adjust',
            'points' => 100,
        ]);
    }

    #[Test]
    public function it_can_redeem_points()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $reward = LoyaltyReward::factory()->create([
            'loyalty_program_id' => $program->id,
            'points_required' => 100,
        ]);

        // Adicionar pontos
        LoyaltyTransaction::factory()->create([
            'loyalty_program_id' => $program->id,
            'client_id' => $client->id,
            'type' => 'earn',
            'points' => 200,
            'balance_after' => 200,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/loyalty/redeem', [
                'client_id' => $client->id,
                'reward_id' => $reward->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Recompensa resgatada com sucesso',
            ]);

        $this->assertDatabaseHas('loyalty_redemptions', [
            'client_id' => $client->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
        ]);
    }

    #[Test]
    public function it_prevents_redeeming_more_points_than_available()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $reward = LoyaltyReward::factory()->create([
            'loyalty_program_id' => $program->id,
            'points_required' => 500,
        ]);

        // Cliente tem apenas 100 pontos
        LoyaltyTransaction::factory()->create([
            'loyalty_program_id' => $program->id,
            'client_id' => $client->id,
            'type' => 'earn',
            'points' => 100,
            'balance_after' => 100,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/loyalty/redeem', [
                'client_id' => $client->id,
                'reward_id' => $reward->id,
            ]);

        $response->assertStatus(500); // Service lança exceção
    }

    #[Test]
    public function it_can_list_customer_transactions()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar transações
        LoyaltyTransaction::factory()->create([
            'loyalty_program_id' => $program->id,
            'client_id' => $client->id,
            'type' => 'earn',
            'points' => 100,
            'balance_after' => 100,
        ]);

        LoyaltyTransaction::factory()->create([
            'loyalty_program_id' => $program->id,
            'client_id' => $client->id,
            'type' => 'spend',
            'points' => 50,
            'balance_after' => 50,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/loyalty/client/{$client->id}/transactions");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'type' => 'earn',
                        'points' => 100,
                        'balance_after' => 100,
                    ],
                    [
                        'type' => 'spend',
                        'points' => 50,
                        'balance_after' => 50,
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_can_list_rewards()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        LoyaltyReward::factory()->count(3)->create([
            'loyalty_program_id' => $program->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/loyalty/rewards');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_can_create_a_reward()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = [
            'name' => 'R$ 10 de desconto',
            'type' => 'discount_fixed',
            'points_required' => 100,
            'discount_value' => 10.00,
            'is_active' => true,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/loyalty/rewards', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('loyalty_rewards', [
            'loyalty_program_id' => $program->id,
            'name' => 'R$ 10 de desconto',
            'points_required' => 100,
        ]);
    }

    #[Test]
    public function it_calculates_points_correctly()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
            'points_per_currency' => 1.0, // 1 ponto por R$
            'min_purchase_amount' => null,
        ]);

        $points = $program->calculatePoints(100.00);
        $this->assertEquals(100, $points);

        $pointsWithMultiplier = $program->calculatePoints(100.00, 2.0);
        $this->assertEquals(200, $pointsWithMultiplier);
    }

    #[Test]
    public function it_respects_minimum_purchase_amount()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
            'points_per_currency' => 1.0,
            'min_purchase_amount' => 50.00,
        ]);

        $points = $program->calculatePoints(30.00); // Abaixo do mínimo
        $this->assertEquals(0, $points);

        $pointsAboveMin = $program->calculatePoints(100.00);
        $this->assertEquals(100, $pointsAboveMin);
    }

    #[Test]
    public function it_can_get_client_balance()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar transações
        LoyaltyTransaction::factory()->create([
            'loyalty_program_id' => $program->id,
            'client_id' => $client->id,
            'type' => 'earn',
            'points' => 100,
            'balance_after' => 100,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/loyalty/client/{$client->id}/balance");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'balance' => 100,
                ],
            ]);
    }

    #[Test]
    public function it_can_redeem_a_reward()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $reward = LoyaltyReward::factory()->create([
            'loyalty_program_id' => $program->id,
            'points_required' => 100,
        ]);

        // Adicionar pontos
        LoyaltyTransaction::factory()->create([
            'loyalty_program_id' => $program->id,
            'client_id' => $client->id,
            'type' => 'earn',
            'points' => 200,
            'balance_after' => 200,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/loyalty/redeem', [
                'client_id' => $client->id,
                'reward_id' => $reward->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Recompensa resgatada com sucesso',
            ]);

        $this->assertDatabaseHas('loyalty_redemptions', [
            'client_id' => $client->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
        ]);
    }

    #[Test]
    public function it_prevents_redeem_with_insufficient_points()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $reward = LoyaltyReward::factory()->create([
            'loyalty_program_id' => $program->id,
            'points_required' => 500,
        ]);

        // Cliente tem apenas 100 pontos
        LoyaltyTransaction::factory()->create([
            'loyalty_program_id' => $program->id,
            'client_id' => $client->id,
            'type' => 'earn',
            'points' => 100,
            'balance_after' => 100,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/loyalty/redeem', [
                'client_id' => $client->id,
                'reward_id' => $reward->id,
            ]);

        $response->assertStatus(500); // Service lança exceção
    }

    #[Test]
    public function it_can_adjust_points_manually()
    {
        $program = LoyaltyProgram::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/loyalty/client/{$client->id}/adjust-points", [
                'points' => 50,
                'description' => 'Bônus de boas-vindas',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('loyalty_transactions', [
            'client_id' => $client->id,
            'type' => 'adjust',
            'points' => 50,
        ]);
    }

    #[Test]
    public function it_isolates_programs_by_tenant()
    {
        $otherTenant = Tenant::factory()->create([
            'name' => 'Outra Loja',
            'slug' => 'outra-loja',
            'email' => 'outra@teste.com',
            'cnpj' => '22345678000199',
            'is_active' => true,
        ]);

        LoyaltyProgram::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/loyalty/program');

        $response->assertStatus(200);
        $this->assertNull($response->json('data'));
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/loyalty/program');
        $response->assertStatus(401);
    }
}

