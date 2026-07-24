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

        // Usa /auth/logout (não /auth/me) como sonda: /auth/me e /orders chamam
        // JWTAuth::parseToken()->authenticate() direto no controller, que resolve
        // contra o guard default da app (web/users) em vez do guard client — bug
        // pré-existente e não relacionado a esta migração (reportado à parte).
        // /auth/logout só invalida o token já parseado pelo middleware do guard
        // client, então prova corretamente que o cookie autenticou na rota.
        $logoutResponse = $this->withCredentials()
            ->withUnencryptedCookies(['client_auth_token' => $cookie->getValue()])
            ->postJson("/api/store/{$this->tenant->slug}/auth/logout");

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout realizado com sucesso',
            ]);
    }
}
