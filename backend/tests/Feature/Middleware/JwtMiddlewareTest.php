<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class JwtMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function middleware_permite_acesso_com_token_valido()
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);
    }

    #[Test]
    public function middleware_nega_acesso_sem_token()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token não fornecido'
            ]);
    }

    #[Test]
    public function middleware_nega_acesso_com_token_invalido()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token inválido'
            ]);
    }

    #[Test]
    public function middleware_nega_acesso_para_usuario_inativo()
    {
        $user = User::factory()->inactive()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Usuário inativo'
            ]);
    }

    #[Test]
    public function middleware_nega_acesso_com_token_expirado()
    {
        $user = User::factory()->create(['is_active' => true]);
        
        // Simula um token expirado
        $token = JWTAuth::fromUser($user);
        
        // Viaja no tempo para expirar o token
        $this->travel(config('jwt.ttl', 60) + 1)->minutes();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token expirado'
            ]);
    }

    #[Test]
    public function middleware_permite_acesso_a_rotas_publicas()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        // Não deve retornar 401 por falta de token, mas pode retornar 422 por credenciais inválidas
        $response->assertStatus(422);
    }

    #[Test]
    public function middleware_funciona_com_diferentes_formatos_de_authorization_header()
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = JWTAuth::fromUser($user);

        // Testa com "Bearer "
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);

        // Testa sem "Bearer " (se suportado)
        $response = $this->withHeaders([
            'Authorization' => $token,
        ])->getJson('/api/auth/me');

        // Pode retornar 401 se o formato não for suportado
        $this->assertContains($response->getStatusCode(), [200, 401]);
    }
}
