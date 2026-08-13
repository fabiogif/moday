<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(AdminUser $admin): string
    {
        return $admin->createToken('test-token')->plainTextToken;
    }

    #[Test]
    public function it_updates_admin_profile_name_and_email(): void
    {
        $admin = AdminUser::factory()->create([
            'name' => 'Admin Original',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withToken($this->adminToken($admin))
            ->putJson('/api/admin/auth/profile', [
                'name' => 'Admin Atualizado',
                'email' => 'novo@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso.',
                'data' => [
                    'name' => 'Admin Atualizado',
                    'email' => 'novo@example.com',
                ],
            ]);

        $this->assertDatabaseHas('admin_users', [
            'id' => $admin->id,
            'name' => 'Admin Atualizado',
            'email' => 'novo@example.com',
        ]);
    }

    #[Test]
    public function it_validates_profile_fields(): void
    {
        $admin = AdminUser::factory()->create();

        $response = $this->withToken($this->adminToken($admin))
            ->putJson('/api/admin/auth/profile', [
                'name' => 'A',
                'email' => 'invalido',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    #[Test]
    public function it_rejects_duplicate_email_on_profile_update(): void
    {
        AdminUser::factory()->create(['email' => 'outro@example.com']);
        $admin = AdminUser::factory()->create(['email' => 'admin@example.com']);

        $response = $this->withToken($this->adminToken($admin))
            ->putJson('/api/admin/auth/profile', [
                'name' => 'Admin',
                'email' => 'outro@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_changes_admin_password_and_revokes_tokens(): void
    {
        $admin = AdminUser::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $token = $this->adminToken($admin);

        $response = $this->withToken($token)
            ->putJson('/api/admin/auth/password', [
                'current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Senha alterada com sucesso. Faça login novamente.',
            ]);

        $admin->refresh();
        $this->assertTrue(Hash::check('newpassword456', $admin->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->postJson('/api/admin/auth/login', [
            'email' => $admin->email,
            'password' => 'newpassword456',
        ])->assertStatus(200)->assertJsonPath('success', true);

        $this->postJson('/api/admin/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ])->assertStatus(422);
    }

    #[Test]
    public function it_rejects_wrong_current_password(): void
    {
        $admin = AdminUser::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withToken($this->adminToken($admin))
            ->putJson('/api/admin/auth/password', [
                'current_password' => 'senha-errada',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'A senha atual está incorreta.',
            ])
            ->assertJsonPath('errors.current_password.0', 'A senha atual está incorreta.');
    }

    #[Test]
    public function it_requires_authentication_for_profile_routes(): void
    {
        $this->putJson('/api/admin/auth/profile', [
            'name' => 'Test',
            'email' => 'test@example.com',
        ])->assertStatus(401);

        $this->putJson('/api/admin/auth/password', [
            'current_password' => 'password123',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ])->assertStatus(401);
    }
}
