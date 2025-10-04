<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RegisterUserTest extends TestCase
{
	use RefreshDatabase;

	#[Test]
	public function usuario_pode_se_registrar_com_sucesso()
	{
		$payload = [
			'name' => 'Usuário Teste',
			'email' => 'usuario.teste@example.com',
			'password' => 'Password123!',
			'password_confirmation' => 'Password123!',
			'phone' => '11999990000',
		];

		$response = $this->postJson('/api/auth/register', $payload);

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
						'is_active',
					],
					'token',
					'expires_in',
				],
			])
			->assertJson([
				'success' => true,
				'message' => 'Usuário registrado com sucesso',
			]);

		$this->assertDatabaseHas('users', [
			'name' => 'Usuário Teste',
			'email' => 'usuario.teste@example.com',
			'phone' => '11999990000',
			'is_active' => true,
		]);
	}

	#[Test]
	public function nao_pode_registrar_com_email_ja_utilizado()
	{
		User::factory()->create([
			'email' => 'duplicado@example.com',
		]);

		$payload = [
			'name' => 'Outro Usuário',
			'email' => 'duplicado@example.com',
			'password' => 'Password123!',
			'password_confirmation' => 'Password123!',
		];

		$response = $this->postJson('/api/auth/register', $payload);

		$response->assertStatus(422)
			->assertJsonValidationErrors(['email']);
	}

	#[Test]
	public function valida_campos_obrigatorios_e_confirmacao_de_senha()
	{
		// Sem dados
		$responseEmpty = $this->postJson('/api/auth/register', []);
		$responseEmpty->assertStatus(422)
			->assertJsonValidationErrors(['name', 'email', 'password']);

		// Sem confirmação correta de senha
		$payload = [
			'name' => 'Usuário',
			'email' => 'user@example.com',
			'password' => 'Password123!',
			'password_confirmation' => 'OutraSenha123!',
		];

		$response = $this->postJson('/api/auth/register', $payload);
		$response->assertStatus(422)
			->assertJsonValidationErrors(['password']);
	}
}


