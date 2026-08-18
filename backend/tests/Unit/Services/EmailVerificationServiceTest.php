<?php

namespace Tests\Unit\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(EmailVerificationService::class);
    }

    #[Test]
    public function send_armazena_hash_e_dispara_mailable(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $result = $this->service->send($user);

        $this->assertTrue($result['success']);
        $this->assertTrue(Cache::has("email_verify:{$user->id}"));
        $this->assertTrue(Cache::has("email_verify_resend:{$user->id}"));

        $payload = Cache::get("email_verify:{$user->id}");
        $this->assertArrayHasKey('hash', $payload);
        $this->assertSame(0, $payload['attempts']);

        Mail::assertSent(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->user->is($user)
                && preg_match('/^\d{6}$/', $mail->code) === 1;
        });
    }

    #[Test]
    public function send_failed_nao_grava_cooldown(): void
    {
        $user = User::factory()->unverified()->create();

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('smtp down'));

        $service = app(EmailVerificationService::class);
        $result = $service->send($user);

        $this->assertFalse($result['success']);
        $this->assertSame('send_failed', $result['error']);
        $this->assertFalse(Cache::has("email_verify:{$user->id}"));
        $this->assertFalse(Cache::has("email_verify_resend:{$user->id}"));
    }

    #[Test]
    public function send_rejeita_usuario_ja_verificado(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $result = $this->service->send($user);

        $this->assertFalse($result['success']);
        $this->assertSame('already_verified', $result['error']);
        Mail::assertNothingSent();
    }

    #[Test]
    public function resend_respeita_cooldown(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        Cache::put("email_verify_resend:{$user->id}", time() + 45, 45);

        $result = $this->service->resend($user);

        $this->assertFalse($result['success']);
        $this->assertSame('resend_cooldown', $result['error']);
        $this->assertArrayHasKey('retry_after', $result);
        $this->assertGreaterThan(0, $result['retry_after']);
        Mail::assertNothingSent();
    }

    #[Test]
    public function verify_aceita_codigo_correto(): void
    {
        $user = User::factory()->unverified()->create();
        Cache::put("email_verify:{$user->id}", [
            'hash' => Hash::make('654321'),
            'attempts' => 0,
        ], 900);

        $result = $this->service->verify($user, '654321');

        $this->assertTrue($result['success']);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertFalse(Cache::has("email_verify:{$user->id}"));
    }

    #[Test]
    public function verify_rejeita_codigo_incorreto_e_incrementa_tentativas(): void
    {
        $user = User::factory()->unverified()->create();
        Cache::put("email_verify:{$user->id}", [
            'hash' => Hash::make('654321'),
            'attempts' => 0,
        ], 900);

        $result = $this->service->verify($user, '111111');

        $this->assertFalse($result['success']);
        $this->assertSame('invalid_code', $result['error']);
        $this->assertSame(1, Cache::get("email_verify:{$user->id}")['attempts']);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function verify_expira_apos_maximo_de_tentativas(): void
    {
        $user = User::factory()->unverified()->create();
        Cache::put("email_verify:{$user->id}", [
            'hash' => Hash::make('654321'),
            'attempts' => 4,
        ], 900);

        $result = $this->service->verify($user, '111111');

        $this->assertFalse($result['success']);
        $this->assertSame('too_many_attempts', $result['error']);
        $this->assertFalse(Cache::has("email_verify:{$user->id}"));
    }

    #[Test]
    public function verify_retorna_expired_quando_nao_ha_codigo(): void
    {
        $user = User::factory()->unverified()->create();

        $result = $this->service->verify($user, '123456');

        $this->assertFalse($result['success']);
        $this->assertSame('expired_code', $result['error']);
    }
}
