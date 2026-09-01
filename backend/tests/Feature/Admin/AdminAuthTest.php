<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_authenticates_admin_with_valid_credentials()
    {
        $admin = AdminUser::factory()->create([
            'email' => 'test@admin.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'test@admin.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'admin' => ['id', 'name', 'email', 'role'],
                    'token',
                ],
            ]);
        
        $this->assertNotNull($response->json('data.token'));
    }

    #[Test]
    public function login_seta_cookie_httponly_e_autentica_sem_header_authorization()
    {
        AdminUser::factory()->create([
            'email' => 'cookie@admin.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $loginResponse = $this->postJson('/api/admin/auth/login', [
            'email' => 'cookie@admin.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $cookie = $loginResponse->headers->getCookies()[0] ?? null;

        $this->assertNotNull($cookie, 'Login deveria retornar um Set-Cookie');
        $this->assertSame('admin_token', $cookie->getName());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', strtolower((string) $cookie->getSameSite()));

        $meResponse = $this->withCredentials()
            ->withUnencryptedCookies(['admin_token' => $cookie->getValue()])
            ->getJson('/api/admin/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'email', 'role', 'permissions'],
            ]);
    }

    #[Test]
    public function it_rejects_invalid_credentials()
    {
        AdminUser::factory()->create([
            'email' => 'test@admin.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'test@admin.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_inactive_admin()
    {
        $admin = AdminUser::factory()->inactive()->create([
            'email' => 'inactive@admin.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'inactive@admin.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    #[Test]
    public function it_updates_last_login_timestamp()
    {
        $admin = AdminUser::factory()->create([
            'email' => 'test@admin.com',
            'password' => Hash::make('password123'),
            'last_login_at' => null,
        ]);

        $this->postJson('/api/admin/auth/login', [
            'email' => 'test@admin.com',
            'password' => 'password123',
        ]);

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);
    }

    #[Test]
    public function it_logouts_successfully()
    {
        $admin = AdminUser::factory()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/admin/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout realizado com sucesso.',
            ]);
    }

    #[Test]
    public function it_returns_authenticated_admin_data()
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/admin/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'name', 'email', 'role', 'permissions',
                ],
            ]);
    }

    #[Test]
    public function it_denies_access_without_token()
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }

    #[Test]
    public function forgot_password_envia_link_para_admin_existente()
    {
        AdminUser::factory()->create(['email' => 'forgot@admin.com']);

        $response = $this->postJson('/api/admin/auth/forgot-password', [
            'email' => 'forgot@admin.com',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'forgot@admin.com']);
    }

    #[Test]
    public function forgot_password_nao_revela_se_email_nao_existe()
    {
        $response = $this->postJson('/api/admin/auth/forgot-password', [
            'email' => 'nao-existe@admin.com',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    #[Test]
    public function reset_password_com_token_valido_atualiza_senha()
    {
        $admin = AdminUser::factory()->create(['email' => 'reset@admin.com']);
        $token = \Illuminate\Support\Facades\Password::broker('admin_users')->createToken($admin);

        $response = $this->postJson('/api/admin/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@admin.com',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $loginResponse = $this->postJson('/api/admin/auth/login', [
            'email' => 'reset@admin.com',
            'password' => 'nova-senha-123',
        ]);
        $loginResponse->assertStatus(200);
    }

    #[Test]
    public function reset_password_com_token_invalido_e_rejeitado()
    {
        AdminUser::factory()->create(['email' => 'reset2@admin.com']);

        $response = $this->postJson('/api/admin/auth/reset-password', [
            'token' => 'token-invalido',
            'email' => 'reset2@admin.com',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ]);

        $response->assertStatus(401)->assertJson(['success' => false]);
    }
}

