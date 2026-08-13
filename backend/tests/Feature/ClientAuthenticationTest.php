<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create(['is_active' => true]);

        $this->tenant = Tenant::factory()->create([
            'slug' => 'loja-teste',
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);
    }

    #[Test]
    public function login_seta_cookie_httponly_e_guard_client_autentica_via_cookie()
    {
        Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'cliente@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginResponse = $this->postJson("/api/store/{$this->tenant->slug}/auth/login", [
            'email' => 'cliente@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $cookie = $loginResponse->headers->getCookies()[0] ?? null;

        $this->assertNotNull($cookie, 'Login deveria retornar um Set-Cookie');
        $this->assertSame('client_auth_token', $cookie->getName());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', strtolower((string) $cookie->getSameSite()));

        $logoutResponse = $this->withCredentials()
            ->withUnencryptedCookies(['client_auth_token' => $cookie->getValue()])
            ->postJson("/api/store/{$this->tenant->slug}/auth/logout");

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout realizado com sucesso',
            ]);
    }

    #[Test]
    public function auth_me_retorna_dados_do_cliente_autenticado()
    {
        // Regressão do bug: me()/getOrders() chamavam JWTAuth::parseToken()
        // ->authenticate() direto, que resolve contra o guard default da app
        // (web/users) em vez do guard client — nunca autenticava corretamente,
        // com ou sem cookie. Corrigido para usar $request->user('client'),
        // que já vem resolvido pelo middleware auth:client da rota.
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'cliente2@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginResponse = $this->postJson("/api/store/{$this->tenant->slug}/auth/login", [
            'email' => 'cliente2@example.com',
            'password' => 'password123',
        ]);
        $token = $loginResponse->json('data.token');

        $meResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson("/api/store/{$this->tenant->slug}/auth/me");

        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['uuid' => $client->uuid, 'email' => $client->email],
            ]);
    }

    #[Test]
    public function auth_me_autentica_via_cookie_sem_header_authorization()
    {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'cliente3@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginResponse = $this->postJson("/api/store/{$this->tenant->slug}/auth/login", [
            'email' => 'cliente3@example.com',
            'password' => 'password123',
        ]);
        $cookie = $loginResponse->headers->getCookies()[0];

        $meResponse = $this->withCredentials()
            ->withUnencryptedCookies(['client_auth_token' => $cookie->getValue()])
            ->getJson("/api/store/{$this->tenant->slug}/auth/me");

        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['uuid' => $client->uuid, 'email' => $client->email],
            ]);
    }

    #[Test]
    public function orders_autentica_o_cliente_correto_mesmo_bug_do_me()
    {
        Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'cliente4@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginResponse = $this->postJson("/api/store/{$this->tenant->slug}/auth/login", [
            'email' => 'cliente4@example.com',
            'password' => 'password123',
        ]);
        $token = $loginResponse->json('data.token');

        $ordersResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson("/api/store/{$this->tenant->slug}/orders");

        $ordersResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['orders' => [], 'total_orders' => 0],
            ]);
    }
}
