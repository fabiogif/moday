<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Profile;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar profile padrão para testes
        Profile::factory()->create([
            'name' => 'user',
            'description' => 'Usuário padrão'
        ]);
    }

    #[Test]
    public function usuario_pode_fazer_login_com_credenciais_validas()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'is_active'
                    ],
                    'token',
                    'expires_in'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login realizado com sucesso'
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    #[Test]
    public function usuario_nao_pode_fazer_login_com_credenciais_invalidas()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Credenciais inválidas'
            ]);
    }

    #[Test]
    public function usuario_inativo_nao_pode_fazer_login()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false
            ]);
    }

    #[Test]
    public function usuario_pode_se_registrar_com_dados_validos()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '11999999999'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'is_active'
                    ],
                    'token',
                    'expires_in'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Usuário registrado com sucesso'
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11999999999',
            'is_active' => true
        ]);
    }

    #[Test]
    public function usuario_nao_pode_se_registrar_com_email_duplicado()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'João Silva',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function usuario_autenticado_pode_acessar_dados_do_perfil()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'is_active'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email
                ]
            ]);
    }

    #[Test]
    public function usuario_pode_fazer_logout()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ]);
    }

    #[Test]
    public function usuario_pode_renovar_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'expires_in'
                ]
            ])
            ->assertJson([
                'success' => true
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertNotEquals($token, $response->json('data.token'));
    }

    #[Test]
    public function requisicao_sem_token_retorna_erro_401()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token não fornecido'
            ]);
    }

    #[Test]
    public function requisicao_com_token_invalido_retorna_erro_401()
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
    public function validacao_de_campos_obrigatorios_no_login()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    #[Test]
    public function validacao_de_campos_obrigatorios_no_registro()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function validacao_de_formato_de_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function validacao_de_confirmacao_de_senha_no_registro()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
