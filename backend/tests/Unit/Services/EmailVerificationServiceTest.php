<?php

namespace Tests\Unit\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Services\EmailService;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailService $emailService;

    private EmailVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailService = Mockery::mock(EmailService::class);
        $this->service = new EmailVerificationService($this->emailService);
    }

    #[Test]
    public function send_armazena_hash_e_dispara_mailable(): void
    {
        $user = User::factory()->unverified()->create();

        $this->emailService
            ->shouldReceive('send')
            ->once()
            ->withArgs(function ($to, $mailable) use ($user) {
                return $to === $user->email
                    && $mailable instanceof EmailVerificationCodeMail
                    && preg_match('/^\d{6}$/', $mailable->code) === 1;
            })
            ->andReturn(true);

        $result = $this->service->send($user);

        $this->assertTrue($result['success']);
        $this->assertTrue(Cache::has("email_verify:{$user->id}"));
        $this->assertTrue(Cache::has("email_verify_resend:{$user->id}"));

        $payload = Cache::get("email_verify:{$user->id}");
        $this->assertArrayHasKey('hash', $payload);
        $this->assertSame(0, $payload['attempts']);
    }

    #[Test]
    public function send_rejeita_usuario_ja_verificado(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->emailService->shouldNotReceive('send');

        $result = $this->service->send($user);

        $this->assertFalse($result['success']);
        $this->assertSame('already_verified', $result['error']);
    }

    #[Test]
    public function resend_respeita_cooldown(): void
    {
        $user = User::factory()->unverified()->create();
        Cache::put("email_verify_resend:{$user->id}", time() + 45, 45);

        $this->emailService->shouldNotReceive('send');

        $result = $this->service->resend($user);

        $this->assertFalse($result['success']);
        $this->assertSame('resend_cooldown', $result['error']);
        $this->assertArrayHasKey('retry_after', $result);
        $this->assertGreaterThan(0, $result['retry_after']);
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
