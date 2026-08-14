<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'array',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1025,
            'mail.provider' => 'smtp',
        ]);

        Mail::fake();

        $this->plan = Plan::factory()->create([
            'is_active' => true,
            'price' => 0,
            'name' => 'Grátis',
            'url' => 'gratis-verify',
        ]);
    }

    private function registerPayload(): array
    {
        return [
            'company_name' => 'Empresa Verify',
            'company_email' => 'empresa-verify@test.com',
            'company_cnpj' => '11222333000181',
            'name' => 'Admin Verify',
            'email' => 'admin-verify@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_id' => $this->plan->id,
        ];
    }

    private function authHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
            'Accept' => 'application/json',
        ];
    }

    private function storeCode(User $user, string $code, int $attempts = 0): void
    {
        Cache::put("email_verify:{$user->id}", [
            'hash' => Hash::make($code),
            'attempts' => $attempts,
        ], 900);
    }

    #[Test]
    public function register_envia_otp_e_marca_email_como_nao_verificado(): void
    {
        $response = $this->postJson('/api/register', $this->registerPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.email_verified', false)
            ->assertJsonPath('data.requires_email_verification', true)
            ->assertJsonPath('data.user.email_verified', false);

        $user = User::where('email', 'admin-verify@test.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        Mail::assertSent(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use ($user) {
            return $mail->user->is($user) && preg_match('/^\d{6}$/', $mail->code) === 1;
        });
    }

    #[Test]
    public function login_inclui_flag_email_verified(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'login-unverified@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login-unverified@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email_verified', false)
            ->assertJsonPath('data.requires_email_verification', true);
    }

    #[Test]
    public function verifica_codigo_valido(): void
    {
        $user = User::factory()->unverified()->create();
        $this->storeCode($user, '123456');

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/auth/email/verify', ['code' => '123456']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email_verified', true);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function rejeita_codigo_invalido(): void
    {
        $user = User::factory()->unverified()->create();
        $this->storeCode($user, '123456');

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/auth/email/verify', ['code' => '000000']);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'invalid_code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function rejeita_codigo_expirado(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/auth/email/verify', ['code' => '123456']);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'expired_code');
    }

    #[Test]
    public function bloqueia_apos_maximo_de_tentativas(): void
    {
        $user = User::factory()->unverified()->create();
        $this->storeCode($user, '123456', 4);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/auth/email/verify', ['code' => '000000']);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'too_many_attempts');

        $this->assertFalse(Cache::has("email_verify:{$user->id}"));
    }

    #[Test]
    public function middleware_bloqueia_rota_tenant_sem_email_verificado(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/subscription/trial-status');

        $response->assertStatus(403)
            ->assertJsonPath('error', 'email_unverified');
    }

    #[Test]
    public function middleware_permite_rota_tenant_com_email_verificado(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/subscription/trial-status');

        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals('email_unverified', $response->json('error'));
    }

    #[Test]
    public function reenvio_respeita_cooldown(): void
    {
        $user = User::factory()->unverified()->create();
        Cache::put("email_verify_resend:{$user->id}", time() + 60, 60);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/auth/email/verification-notification');

        $response->assertStatus(429)
            ->assertJsonPath('error', 'resend_cooldown');
    }

    #[Test]
    public function reenvio_envia_novo_codigo(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/auth/email/verification-notification');

        $response->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(EmailVerificationCodeMail::class);
        $this->assertTrue(Cache::has("email_verify:{$user->id}"));
    }

    #[Test]
    public function verify_valida_formato_do_codigo(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/auth/email/verify', ['code' => '12ab']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function auth_me_e_logout_ficam_acessiveis_sem_email_verificado(): void
    {
        $user = User::factory()->unverified()->create();
        $headers = $this->authHeaders($user);

        $this->withHeaders($headers)
            ->getJson('/api/auth/me')
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson('/api/auth/logout')
            ->assertOk();
    }
}
