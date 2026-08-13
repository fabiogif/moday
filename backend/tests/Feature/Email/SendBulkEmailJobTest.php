<?php

namespace Tests\Feature\Email;

use App\Jobs\SendBulkEmailJob;
use App\Mail\AdminBulkEmail;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendBulkEmailJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_admin_bulk_email(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => 'empresa@teste.com',
            'name' => 'Restaurante Teste',
        ]);

        $job = new SendBulkEmailJob(
            'empresa@teste.com',
            'Comunicado importante',
            'Olá {name}, temos novidades!',
            $tenant,
            1,
        );

        $job->handle();

        Mail::assertSent(AdminBulkEmail::class, function (AdminBulkEmail $mail) use ($tenant) {
            return $mail->hasTo('empresa@teste.com')
                && $mail->emailSubject === 'Comunicado importante'
                && $mail->tenant->id === $tenant->id;
        });
    }

    public function test_job_skips_when_tenant_not_found(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create(['email' => 'empresa@teste.com']);

        $job = new SendBulkEmailJob(
            'empresa@teste.com',
            'Assunto',
            'Mensagem',
            $tenant,
            1,
        );

        $tenant->delete();
        $job->handle();

        Mail::assertNothingSent();
    }
}
