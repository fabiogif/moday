<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientValidateStepApiTest extends TestCase
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
    public function post_client_validate_nao_e_capturado_pela_rota_de_show_ou_update(): void
    {
        $response = $this->withHeaders($this->auth())->postJson('/api/client/validate', [
            'step' => 0,
            'name' => 'Maria Souza',
            'cpf' => '52998224725',
            'email' => 'maria@example.com',
            'phone' => '11987654321',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function retorna_erro_de_validacao_quando_email_ja_esta_cadastrado_no_tenant(): void
    {
        Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'ja-existe@example.com',
        ]);

        $response = $this->withHeaders($this->auth())->postJson('/api/client/validate', [
            'step' => 0,
            'name' => 'Novo Cliente',
            'email' => 'ja-existe@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function nao_acusa_duplicidade_ao_revalidar_o_proprio_cliente_em_edicao(): void
    {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'cliente@example.com',
        ]);

        $response = $this->withHeaders($this->auth())->postJson('/api/client/validate', [
            'step' => 0,
            'client_id' => $client->id,
            'name' => 'Cliente Editado',
            'email' => 'cliente@example.com',
        ]);

        $response->assertOk();
    }

    #[Test]
    public function nao_permite_validar_client_id_de_outro_tenant(): void
    {
        $otherPlan = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        $otherClient = Client::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->withHeaders($this->auth())->postJson('/api/client/validate', [
            'step' => 0,
            'client_id' => $otherClient->id,
            'name' => 'Tentativa Invasão',
        ]);

        $response->assertStatus(404);
    }
}
