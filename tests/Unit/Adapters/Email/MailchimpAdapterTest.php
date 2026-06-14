<?php

namespace Tests\Unit\Adapters\Email;

use App\Adapters\Email\MailchimpAdapter;
use App\Mail\WelcomeCompanyMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailchimpAdapterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o adaptador Mailchimp pode ser instanciado
     */
    public function test_mailchimp_adapter_can_be_instantiated(): void
    {
        $adapter = new MailchimpAdapter();

        $this->assertInstanceOf(MailchimpAdapter::class, $adapter);
    }

    /**
     * Testa se o adaptador Mailchimp retorna o nome correto do provedor
     */
    public function test_mailchimp_adapter_returns_correct_provider_name(): void
    {
        $adapter = new MailchimpAdapter();

        $this->assertEquals('mailchimp', $adapter->getProviderName());
    }

    /**
     * Testa se o adaptador Mailchimp verifica configuração corretamente
     */
    public function test_mailchimp_adapter_checks_configuration(): void
    {
        $adapter = new MailchimpAdapter();

        $isConfigured = $adapter->isConfigured();

        $this->assertIsBool($isConfigured);
    }
}






