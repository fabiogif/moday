<?php

namespace Tests\Feature\Email;

use App\Mail\ContactFormMail;
use App\Mail\WelcomeCompanyMail;
use App\Mail\PlanConfirmationMail;
use App\Mail\EventNotificationMail;
use App\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Testes de integração do sistema de e-mail com OCI Email Delivery + Mailcow
 *
 * Valida:
 *  - Configuração SMTP (OCI Email Delivery)
 *  - Endereços configurados: contato@, atendimento@, pix@, noreply@
 *  - Envio de cada tipo de e-mail transacional
 *  - Parâmetros obrigatórios (from, to, subject, reply-to)
 */
class MailcowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Configuração SMTP e Endereços
    // =========================================================================

    public function test_oci_smtp_host_is_configured(): void
    {
        if (config('mail.default') === 'array' || app()->environment('testing')) {
            $this->markTestSkipped('SMTP OCI não é validado no ambiente de testes (mailer=array).');
        }

        $host = config('mail.mailers.smtp.host');

        $this->assertNotEmpty($host, 'MAIL_HOST não está configurado');
        $this->assertStringContainsString('oraclecloud.com', $host,
            'MAIL_HOST deve usar OCI Email Delivery');
    }

    public function test_smtp_port_is_587_for_starttls(): void
    {
        if (config('mail.default') === 'array' || app()->environment('testing')) {
            $this->markTestSkipped('SMTP OCI não é validado no ambiente de testes (mailer=array).');
        }

        $this->assertEquals(587, config('mail.mailers.smtp.port'),
            'MAIL_PORT deve ser 587 (STARTTLS)');
    }

    public function test_smtp_encryption_is_tls(): void
    {
        if (config('mail.default') === 'array' || app()->environment('testing')) {
            $this->markTestSkipped('SMTP OCI não é validado no ambiente de testes (mailer=array).');
        }

        $encryption = config('mail.mailers.smtp.encryption');
        $this->assertEquals('tls', $encryption,
            'MAIL_ENCRYPTION deve ser tls (STARTTLS)');
    }

    public function test_from_address_is_noreply(): void
    {
        $from = (string) config('mail.from.address');
        $this->assertMatchesRegularExpression(
            '/^noreply@(distribtec|albatec)\.com\.br$/',
            $from,
            'MAIL_FROM_ADDRESS deve ser noreply@distribtec.com.br (ou legado albatec)'
        );
    }

    public function test_from_name_is_set(): void
    {
        $this->assertNotEmpty(config('mail.from.name'),
            'MAIL_FROM_NAME deve estar configurado');
    }

    public function test_contact_address_is_configured(): void
    {
        $address = (string) config('mail.contact_to');
        $this->assertMatchesRegularExpression(
            '/^contato@(distribtec|albatec)\.com\.br$/',
            $address,
            'MAIL_CONTACT_TO deve usar domínio distribtec/albatec'
        );
    }

    public function test_support_address_is_configured(): void
    {
        $address = (string) config('mail.support_to');
        $this->assertMatchesRegularExpression(
            '/^atendimento@(distribtec|albatec)\.com\.br$/',
            $address,
            'MAIL_SUPPORT_TO deve usar domínio distribtec/albatec'
        );
    }

    public function test_pix_address_is_configured(): void
    {
        $address = (string) config('mail.pix_to');
        $this->assertMatchesRegularExpression(
            '/^pix@(distribtec|albatec)\.com\.br$/',
            $address,
            'MAIL_PIX_TO deve usar domínio distribtec/albatec'
        );
    }

    public function test_all_four_mailboxes_have_albatec_domain(): void
    {
        $addresses = [
            config('mail.from.address'),
            config('mail.contact_to'),
            config('mail.support_to'),
            config('mail.pix_to'),
        ];

        foreach ($addresses as $address) {
            $this->assertMatchesRegularExpression(
                '/@(distribtec|albatec)\.com\.br$/',
                (string) $address,
                "Endereço '{$address}' deve pertencer ao domínio @distribtec.com.br (ou legado albatec)"
            );
        }
    }

    // =========================================================================
    // ContactFormMail
    // =========================================================================

    public function test_contact_email_is_sent_to_contato_address(): void
    {
        Mail::fake();

        app(ContactService::class)->send([
            'firstName' => 'Maria',
            'lastName' => 'Santos',
            'email' => 'maria@cliente.com',
            'subject' => 'Problema com pedido',
            'message' => 'Meu pedido não foi entregue.',
        ]);

        Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail) {
            return $mail->hasTo(config('mail.contact_to'));
        });
    }

    public function test_contact_email_reply_to_is_sender(): void
    {
        $mail = new ContactFormMail(
            'Carlos Teste',
            'carlos@teste.com',
            'Assunto qualquer',
            'Mensagem de teste.',
        );

        $envelope = $mail->envelope();

        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals('carlos@teste.com', $envelope->replyTo[0]->address);
        $this->assertEquals('Carlos Teste', $envelope->replyTo[0]->name);
    }

    public function test_contact_email_subject_includes_visitor_subject(): void
    {
        $mail = new ContactFormMail(
            'Ana Lima',
            'ana@teste.com',
            'Solicitar orçamento',
            'Quero um orçamento.',
        );

        $this->assertStringContainsString(
            'Solicitar orçamento',
            $mail->envelope()->subject
        );
    }

    public function test_contact_api_endpoint_sends_email(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', [
            'firstName' => 'Pedro',
            'lastName' => 'Alves',
            'email' => 'pedro@teste.com',
            'subject' => 'Teste de integração',
            'message' => 'Mensagem de teste via API.',
        ])->assertOk()->assertJsonPath('success', true);

        Mail::assertSent(ContactFormMail::class);
    }

    public function test_contact_api_rejects_missing_required_fields(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', [
            'firstName' => 'Pedro',
        ])->assertStatus(422)->assertJsonPath('success', false);

        Mail::assertNothingSent();
    }

    public function test_contact_api_rejects_invalid_email_format(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', [
            'firstName' => 'Pedro',
            'lastName' => 'Alves',
            'email' => 'nao-e-email',
            'subject' => 'Teste',
            'message' => 'Mensagem.',
        ])->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_contact_api_rejects_short_message(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', [
            'firstName' => 'Pedro',
            'lastName' => 'Alves',
            'email' => 'pedro@teste.com',
            'subject' => 'Teste',
            'message' => 'Curto',
        ])->assertStatus(422);

        Mail::assertNothingSent();
    }

    // =========================================================================
    // WelcomeCompanyMail
    // =========================================================================

    public function test_welcome_email_subject_is_correct(): void
    {
        $tenant = \App\Models\Tenant::factory()->make(['name' => 'Empresa ABC']);
        $user   = \App\Models\User::factory()->make(['email' => 'dono@empresa.com']);
        $plan   = \App\Models\Plan::factory()->make(['name' => 'Básico']);

        $mail = new WelcomeCompanyMail($tenant, $user, $plan);

        $this->assertStringContainsString('Bem-vindo', $mail->envelope()->subject);
    }

    public function test_welcome_email_reply_to_is_support(): void
    {
        $tenant = \App\Models\Tenant::factory()->make();
        $user   = \App\Models\User::factory()->make();
        $plan   = \App\Models\Plan::factory()->make();

        $mail = new WelcomeCompanyMail($tenant, $user, $plan);
        $envelope = $mail->envelope();

        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals(
            config('mail.support_to'),
            $envelope->replyTo[0]->address,
            'Reply-to deve ser atendimento@albatec.com.br'
        );
    }

    public function test_welcome_email_content_has_login_url(): void
    {
        $tenant = \App\Models\Tenant::factory()->make();
        $user   = \App\Models\User::factory()->make();
        $plan   = \App\Models\Plan::factory()->make();

        $mail = new WelcomeCompanyMail($tenant, $user, $plan);
        $content = $mail->content();

        $this->assertNotEmpty($content->with['loginUrl'],
            'loginUrl deve estar no conteúdo do e-mail');
    }

    // =========================================================================
    // Fila de E-mails
    // =========================================================================

    public function test_contact_form_mail_is_queueable(): void
    {
        $uses = class_uses_recursive(ContactFormMail::class);

        $this->assertContains(
            \Illuminate\Bus\Queueable::class,
            $uses,
            'ContactFormMail deve usar Queueable'
        );
    }

    public function test_welcome_company_mail_is_queueable(): void
    {
        $uses = class_uses_recursive(WelcomeCompanyMail::class);

        $this->assertContains(
            \Illuminate\Bus\Queueable::class,
            $uses,
            'WelcomeCompanyMail deve usar Queueable'
        );
    }

    // =========================================================================
    // SMTP Adapter
    // =========================================================================

    public function test_smtp_adapter_is_configured(): void
    {
        $adapter = app(\App\Adapters\Email\SmtpAdapter::class);
        $this->assertTrue($adapter->isConfigured(),
            'SmtpAdapter deve estar configurado (MAIL_HOST + MAIL_PORT)');
    }

    public function test_smtp_adapter_provider_name_is_smtp(): void
    {
        $adapter = app(\App\Adapters\Email\SmtpAdapter::class);
        $this->assertEquals('smtp', $adapter->getProviderName());
    }

    public function test_smtp_adapter_sends_via_mail_facade(): void
    {
        Mail::fake();

        $adapter = app(\App\Adapters\Email\SmtpAdapter::class);
        $mail    = new ContactFormMail('Teste', 'teste@teste.com', 'S', 'M');

        $result = $adapter->send(config('mail.contact_to'), $mail);

        $this->assertTrue($result);
        Mail::assertSent(ContactFormMail::class);
    }

    public function test_smtp_adapter_send_bulk_returns_counts(): void
    {
        Mail::fake();

        $adapter = app(\App\Adapters\Email\SmtpAdapter::class);
        $mail    = new ContactFormMail('Teste', 'teste@teste.com', 'S', 'M');

        $result = $adapter->sendBulk([
            config('mail.contact_to'),
            config('mail.support_to'),
        ], $mail);

        $this->assertEquals(2, $result['sent']);
        $this->assertEquals(0, $result['failed']);
    }
}
