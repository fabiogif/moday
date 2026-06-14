<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientApiTest extends TestCase
{
    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    private function validB2bPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Farmácia Central LTDA',
            'trade_name' => 'Central Farma',
            'cnpj' => '11.222.333/0001-81',
            'client_type' => 'farmacia',
            'credit_limit' => 5000,
            'payment_term_days' => 30,
            'email' => 'compras@centralfarma.test',
            'phone' => '11987654321',
            'city' => 'São Paulo',
            'state' => 'SP',
            'is_active' => true,
        ], $overrides);
    }

    #[Test]
    public function guest_cannot_list_clients(): void
    {
        $this->getJson('/api/clients')->assertStatus(401);
    }

    #[Test]
    public function it_lists_only_tenant_clients(): void
    {
        Client::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => Plan::factory()->create()->id]);
        Client::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeaders($this->auth())
            ->getJson('/api/clients')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_creates_b2b_client(): void
    {
        $payload = $this->validB2bPayload();

        $response = $this->withHeaders($this->auth())
            ->postJson('/api/clients', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.company_name', 'Farmácia Central LTDA')
            ->assertJsonPath('data.client_type', 'farmacia');

        $this->assertDatabaseHas('clients', [
            'tenant_id' => $this->tenant->id,
            'company_name' => 'Farmácia Central LTDA',
            'client_type' => 'farmacia',
        ]);
    }

    #[Test]
    public function it_shows_client_by_id(): void
    {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_name' => 'Hospital Regional',
        ]);

        $this->withHeaders($this->auth())
            ->getJson("/api/clients/{$client->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $client->id);
    }

    #[Test]
    public function it_updates_client(): void
    {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_name' => 'Nome Original',
            'credit_limit' => 1000,
        ]);

        $this->withHeaders($this->auth())
            ->putJson("/api/clients/{$client->id}", [
                'company_name' => 'Nome Atualizado',
                'credit_limit' => 7500,
                'payment_term_days' => 45,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.company_name', 'Nome Atualizado');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'company_name' => 'Nome Atualizado',
            'credit_limit' => 7500,
        ]);
    }

    #[Test]
    public function it_deletes_client(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->deleteJson("/api/clients/{$client->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    #[Test]
    public function it_cannot_access_client_from_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => Plan::factory()->create()->id]);
        $client = Client::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeaders($this->auth())
            ->getJson("/api/clients/{$client->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function it_rejects_invalid_cnpj_on_create(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/api/clients', $this->validB2bPayload(['cnpj' => '11.111.111/1111-11']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cnpj']);
    }
}
