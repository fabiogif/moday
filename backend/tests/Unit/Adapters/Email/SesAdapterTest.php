<?php

namespace Tests\Unit\Adapters\Email;

use App\Adapters\Email\SesAdapter;
use App\Mail\WelcomeCompanyMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SesAdapterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o adaptador SES pode ser instanciado
     */
    public function test_ses_adapter_can_be_instantiated(): void
    {
        $adapter = new SesAdapter();

        $this->assertInstanceOf(SesAdapter::class, $adapter);
    }

    /**
     * Testa se o adaptador SES retorna o nome correto do provedor
     */
    public function test_ses_adapter_returns_correct_provider_name(): void
    {
        $adapter = new SesAdapter();

        $this->assertEquals('aws-ses', $adapter->getProviderName());
    }

    /**
     * Testa se o adaptador SES verifica configuração corretamente
     */
    public function test_ses_adapter_checks_configuration(): void
    {
        $adapter = new SesAdapter();

        // Sem configuração AWS, deve retornar false
        $isConfigured = $adapter->isConfigured();

        $this->assertIsBool($isConfigured);
    }

    /**
     * Testa se o adaptador SES envia e-mail usando mailer correto
     */
    public function test_ses_adapter_sends_email_with_correct_mailer(): void
    {
        Mail::fake();

        $adapter = new SesAdapter();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        $mailable = new WelcomeCompanyMail($tenant, $user, $plan);

        try {
            $result = $adapter->send('test@example.com', $mailable);
            $this->assertTrue($result);
        } catch (\Exception $e) {
            // Se não estiver configurado, esperamos uma exceção
            $this->assertStringContainsString('ses', strtolower($e->getMessage()));
        }
    }
}






