<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function forgot_password_retorna_mesma_mensagem_para_email_existente_e_inexistente()
    {
        Notification::fake();

        User::factory()->create(['email' => 'existe@example.com', 'is_active' => true]);

        $comUsuario = $this->postJson('/api/auth/forgot-password', ['email' => 'existe@example.com']);
        $semUsuario = $this->postJson('/api/auth/forgot-password', ['email' => 'nao-existe@example.com']);

        $comUsuario->assertStatus(200)->assertJson(['success' => true]);
        $semUsuario->assertStatus(200)->assertJson(['success' => true]);

        $this->assertSame(
            $comUsuario->json('message'),
            $semUsuario->json('message'),
            'A resposta não deve permitir descobrir se o e-mail existe (enumeração de usuário).'
        );
    }

    #[Test]
    public function forgot_password_dispara_notificacao_apenas_para_email_existente()
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'existe@example.com', 'is_active' => true]);

        $this->postJson('/api/auth/forgot-password', ['email' => 'existe@example.com']);
        $this->postJson('/api/auth/forgot-password', ['email' => 'nao-existe@example.com']);

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertCount(1);
    }

    #[Test]
    public function forgot_password_valida_formato_do_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', ['email' => 'invalido']);

        $response->assertStatus(422);
    }
}
