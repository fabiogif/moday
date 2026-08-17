<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
    }

    #[Test]
    public function login_com_credenciais_validas_retorna_sucesso()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_active' => true
        ]);

        $result = $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Login realizado com sucesso', $result['message']);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertNotEmpty($result['token']);
        $this->assertEquals($user->id, $result['user']->id);
    }

    #[Test]
    public function login_com_credenciais_invalidas_retorna_erro()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $result = $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Credenciais inválidas', $result['message']);
    }

    #[Test]
    public function login_com_usuario_inativo_retorna_erro()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_active' => false
        ]);

        $result = $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Usuário inativo', $result['message']);
    }

    #[Test]
    public function registro_com_dados_validos_cria_usuario()
    {
        $tenant = \App\Models\Tenant::factory()->create();
        
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'phone' => '11999999999',
            'tenant_id' => $tenant->id
        ];

        $result = $this->authService->register($userData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Usuário registrado com sucesso', $result['message']);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertNotEmpty($result['token']);

        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11999999999',
            'is_active' => true,
            'tenant_id' => $tenant->id
        ]);
    }

    #[Test]
    public function login_atualiza_ultimo_acesso()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_active' => true,
            'last_login_at' => null
        ]);

        $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    #[Test]
    public function login_armazena_dados_no_cache()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_active' => true
        ]);

        $this->authService->login([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->assertTrue(Cache::has("user_data_{$user->id}"));
    }

    #[Test]
    public function has_permission_verifica_permissoes_corretamente()
    {
        $user = User::factory()->create();
        
        // Mock das permissões no cache
        Cache::put("user_permissions_{$user->id}", ['create_posts', 'edit_posts'], 1800);

        $hasPermission = $this->authService->hasPermission($user, 'create_posts');
        $this->assertTrue($hasPermission);

        $hasNoPermission = $this->authService->hasPermission($user, 'delete_posts');
        $this->assertFalse($hasNoPermission);
    }

    #[Test]
    public function invalidate_user_cache_remove_dados_do_cache()
    {
        $user = User::factory()->create();
        
        // Adiciona dados no cache
        Cache::put("user_data_{$user->id}", $user, 3600);
        Cache::put("user_permissions_{$user->id}", ['permission1'], 1800);

        $this->authService->invalidateUserCache($user->id);

        $this->assertFalse(Cache::has("user_data_{$user->id}"));
        $this->assertFalse(Cache::has("user_permissions_{$user->id}"));
    }

    #[Test]
    public function update_profile_atualiza_dados_e_cache()
    {
        $user = User::factory()->create([
            'name' => 'Nome Original'
        ]);

        $result = $this->authService->updateProfile($user, [
            'name' => 'Nome Atualizado',
            'phone' => '11999999999'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Perfil atualizado com sucesso', $result['message']);
        $this->assertEquals('Nome Atualizado', $result['user']->name);
        $this->assertEquals('11999999999', $result['user']->phone);

        // Verifica se os dados foram atualizados no banco
        $user->refresh();
        $this->assertEquals('Nome Atualizado', $user->name);
        $this->assertEquals('11999999999', $user->phone);

        // Verifica se o cache foi atualizado
        $this->assertTrue(Cache::has("user_data_{$user->id}"));
    }
}
