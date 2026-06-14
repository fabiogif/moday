<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateUserProfileTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTenantUser(User $user)
    {
        $token = auth('api')->login($user);

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ]);
    }

    #[Test]
    public function it_updates_user_name_and_email(): void
    {
        $tenant = Tenant::factory()->create(['account_status' => 'active']);
        $user = User::factory()->for($tenant)->create([
            'name' => 'Nome Original',
            'email' => 'original@example.com',
        ]);

        $response = $this->actingAsTenantUser($user)->putJson('/api/auth/profile', [
            'name' => 'Nome Atualizado',
            'email' => 'novo@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'novo@example.com')
            ->assertJsonPath('data.name', 'Nome Atualizado');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'novo@example.com',
            'name' => 'Nome Atualizado',
        ]);
    }

    #[Test]
    public function it_updates_password_when_current_password_is_valid(): void
    {
        $tenant = Tenant::factory()->create(['account_status' => 'active']);
        $user = User::factory()->for($tenant)->create([
            'password' => Hash::make('senha-atual'),
        ]);

        $response = $this->actingAsTenantUser($user)->putJson('/api/auth/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'senha-atual',
            'new_password' => 'nova-senha-123',
            'new_password_confirmation' => 'nova-senha-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();
        $this->assertTrue(Hash::check('nova-senha-123', $user->password));
    }

    #[Test]
    public function it_rejects_duplicate_email(): void
    {
        $tenant = Tenant::factory()->create(['account_status' => 'active']);
        User::factory()->for($tenant)->create(['email' => 'existente@example.com']);
        $user = User::factory()->for($tenant)->create(['email' => 'meu@example.com']);

        $response = $this->actingAsTenantUser($user)->putJson('/api/auth/profile', [
            'name' => $user->name,
            'email' => 'existente@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->putJson('/api/auth/profile', [
            'name' => 'Teste',
            'email' => 'teste@example.com',
        ]);

        $response->assertUnauthorized();
    }
}
