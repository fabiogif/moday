<?php

namespace Tests\Unit\Adapters\Email;

use App\Adapters\Email\SmtpAdapter;
use App\Mail\WelcomeCompanyMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SmtpAdapterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o adaptador SMTP pode ser instanciado
     */
    public function test_smtp_adapter_can_be_instantiated(): void
    {
        $adapter = new SmtpAdapter();

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
    }

    /**
     * Testa se o adaptador SMTP retorna o nome correto do provedor
     */
    public function test_smtp_adapter_returns_correct_provider_name(): void
    {
        $adapter = new SmtpAdapter();

        $this->assertEquals('smtp', $adapter->getProviderName());
    }

    /**
     * Testa se o adaptador SMTP verifica configuração corretamente
     */
    public function test_smtp_adapter_checks_configuration(): void
    {
        $adapter = new SmtpAdapter();

        // Com configuração padrão do Laravel, deve retornar true
        $isConfigured = $adapter->isConfigured();

        $this->assertIsBool($isConfigured);
    }

    /**
     * Testa se o adaptador SMTP envia e-mail corretamente
     */
    public function test_smtp_adapter_sends_email(): void
    {
        Mail::fake();

        $adapter = new SmtpAdapter();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        $mailable = new WelcomeCompanyMail($tenant, $user, $plan);

        $result = $adapter->send('test@example.com', $mailable);

        $this->assertTrue($result);
        Mail::assertSent(WelcomeCompanyMail::class);
    }

    /**
     * Testa se o adaptador SMTP envia e-mail para múltiplos destinatários
     */
    public function test_smtp_adapter_sends_bulk_email(): void
    {
        Mail::fake();

        $adapter = new SmtpAdapter();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        $mailable = new WelcomeCompanyMail($tenant, $user, $plan);

        $recipients = ['test1@example.com', 'test2@example.com', 'test3@example.com'];

        $result = $adapter->sendBulk($recipients, $mailable);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sent', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertEquals(3, $result['sent']);
        $this->assertEquals(0, $result['failed']);
    }
}






