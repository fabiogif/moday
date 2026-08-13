<?php

namespace Tests\Feature\Email;

use App\Mail\TestMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_test_command_sends_test_email(): void
    {
        Mail::fake();

        $this->artisan('mail:test', [
            'email' => 'destino@example.com',
            '--no-interaction' => true,
        ])->assertSuccessful();

        Mail::assertSent(TestMail::class, function (TestMail $mail) {
            return $mail->hasTo('destino@example.com');
        });
    }

    public function test_mail_test_command_rejects_invalid_email(): void
    {
        Mail::fake();

        $this->artisan('mail:test', [
            'email' => 'invalido',
            '--no-interaction' => true,
        ])->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_mail_test_command_can_queue_email(): void
    {
        Mail::fake();

        $this->artisan('mail:test', [
            'email' => 'fila@example.com',
            '--queue' => true,
            '--no-interaction' => true,
        ])->assertSuccessful();

        Mail::assertQueued(TestMail::class, function (TestMail $mail) {
            return $mail->hasTo('fila@example.com');
        });
    }
}
