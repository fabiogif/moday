<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Profile;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class UserIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar estrutura básica
        $tenant = Tenant::factory()->create();
        $profile = Profile::factory()->create();
        $permission = Permission::factory()->withActionResource('read', 'users')->create();
        
        $profile->permissions()->attach($permission);
        
        $this->user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'profile_id' => $profile->id,
            'is_active' => true
        ]);
        
        $this->token = JWTAuth::fromUser($this->user);
    }

    #[Test]
    public function fluxo_completo_de_autenticacao_funciona()
    {
        // 1. Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password'
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');

        // 2. Acessar dados do usuário
        $meResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonFragment([
                'email' => $this->user->email
            ]);

        // 3. Refresh do token
        $refreshResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $refreshResponse->assertStatus(200);
        $newToken = $refreshResponse->json('data.token');
        $this->assertNotEquals($token, $newToken);

        // 4. Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $newToken,
        ])->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200);
    }

    #[Test]
    public function usuario_com_tenant_acessa_dados_corretos()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'tenant' => [
                        'id',
                        'name',
                        'slug',
                        'is_active'
                    ]
                ]
            ])
            ->assertJsonFragment([
                'tenant' => [
                    'id' => $this->user->tenant->id,
                    'name' => $this->user->tenant->name,
                    'slug' => $this->user->tenant->slug,
                    'is_active' => $this->user->tenant->is_active,
                ]
            ]);
    }

    #[Test]
    public function usuario_com_permissoes_acessa_dados_corretos()
    {
        $this->user->load('profile.permissions');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'profile' => [
                        'id',
                        'name',
                        'description',
                        'permissions' => [
                            '*' => [
                                'id',
                                'name',
                                'description'
                            ]
                        ]
                    ]
                ]
            ]);

        $permissions = $response->json('data.profile.permissions');
        $this->assertCount(1, $permissions);
        $this->assertEquals('read_users', $permissions[0]['name']);
    }

    #[Test]
    public function cache_de_usuario_funciona_corretamente()
    {
        // Login para criar cache
        $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password'
        ]);

        // Verifica se o cache foi criado
        $this->assertTrue(Cache::has("user_data_{$this->user->id}"));

        // Busca dados do usuário
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);

        // Verifica se o cache ainda existe
        $this->assertTrue(Cache::has("user_data_{$this->user->id}"));
    }

    #[Test]
    public function registro_completo_com_tenant_funciona()
    {
        $tenant = Tenant::factory()->create();

        $userData = [
            'name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '11999999999',
            'tenant_id' => $tenant->id
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'tenant' => [
                            'id',
                            'name'
                        ]
                    ],
                    'token'
                ]
            ]);

        // Verifica se o usuário foi criado no banco
        $this->assertDatabaseHas('users', [
            'name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'tenant_id' => $tenant->id
        ]);

        // Verifica se o cache foi criado
        $userId = $response->json('data.user.id');
        $this->assertTrue(Cache::has("user_data_{$userId}"));
    }

    #[Test]
    public function multiplos_usuarios_do_mesmo_tenant_funcionam()
    {
        $tenant = $this->user->tenant;
        
        $user2 = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true
        ]);

        $token2 = JWTAuth::fromUser($user2);

        // Ambos usuários devem conseguir acessar seus dados
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/auth/me');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/auth/me');

        $response1->assertStatus(200)
            ->assertJsonFragment(['id' => $this->user->id]);

        $response2->assertStatus(200)
            ->assertJsonFragment(['id' => $user2->id]);

        // Ambos devem ter o mesmo tenant
        $this->assertEquals(
            $response1->json('data.tenant.id'),
            $response2->json('data.tenant.id')
        );
    }

    #[Test]
    public function desativacao_de_usuario_impede_acesso()
    {
        // Usuário ativo consegue acessar
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);

        // Desativa o usuário
        $this->user->update(['is_active' => false]);

        // Usuário inativo não consegue mais acessar
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Usuário inativo'
            ]);
    }
}
