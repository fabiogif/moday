<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderValidateStepApiTest extends TestCase
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
        $this->grantFullAccess($this->user, $this->tenant);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function post_order_validate_nao_e_capturado_pela_rota_de_show_ou_update(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->auth())->postJson('/api/order/validate', [
            'step' => 0,
            'token_company' => $this->tenant->uuid,
            'client_id' => $client->uuid,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function rejeita_cliente_de_outro_tenant(): void
    {
        $otherPlan = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        $otherClient = Client::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->withHeaders($this->auth())->postJson('/api/order/validate', [
            'step' => 0,
            'token_company' => $this->tenant->uuid,
            'client_id' => $otherClient->uuid,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client_id']);
    }

    #[Test]
    public function valida_produtos_da_etapa_sem_exigir_pagamento(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->auth())->postJson('/api/order/validate', [
            'step' => 1,
            'token_company' => $this->tenant->uuid,
            'products' => [
                ['identify' => $product->uuid, 'qty' => 2, 'price' => 10],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
