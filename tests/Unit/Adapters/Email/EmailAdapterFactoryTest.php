<?php

namespace Tests\Unit\Adapters\Email;

use App\Adapters\Email\EmailAdapterFactory;
use App\Adapters\Email\SmtpAdapter;
use App\Adapters\Email\SesAdapter;
use App\Adapters\Email\PostmarkAdapter;
use App\Adapters\Email\MailchimpAdapter;
use Tests\TestCase;

class EmailAdapterFactoryTest extends TestCase
{
    /**
     * Testa se o factory cria o adaptador SMTP corretamente
     */
    public function test_factory_creates_smtp_adapter(): void
    {
        $adapter = EmailAdapterFactory::create('smtp');

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
        $this->assertEquals('smtp', $adapter->getProviderName());
    }

    /**
     * Testa se o factory cria o adaptador SES corretamente
     */
    public function test_factory_creates_ses_adapter(): void
    {
        $adapter = EmailAdapterFactory::create('ses');

        $this->assertInstanceOf(SesAdapter::class, $adapter);
        $this->assertEquals('aws-ses', $adapter->getProviderName());
    }

    /**
     * Testa se o factory cria o adaptador Postmark corretamente
     */
    public function test_factory_creates_postmark_adapter(): void
    {
        $adapter = EmailAdapterFactory::create('postmark');

        $this->assertInstanceOf(PostmarkAdapter::class, $adapter);
        $this->assertEquals('postmark', $adapter->getProviderName());
    }

    /**
     * Testa se o factory cria o adaptador Mailchimp corretamente
     */
    public function test_factory_creates_mailchimp_adapter(): void
    {
        $adapter = EmailAdapterFactory::create('mailchimp');

        $this->assertInstanceOf(MailchimpAdapter::class, $adapter);
        $this->assertEquals('mailchimp', $adapter->getProviderName());
    }

    /**
     * Testa se o factory lança exceção para provedor inválido
     */
    public function test_factory_throws_exception_for_invalid_provider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('não é suportado');

        EmailAdapterFactory::create('invalid-provider');
    }

    /**
     * Testa se o factory retorna o adaptador padrão
     */
    public function test_factory_returns_default_adapter(): void
    {
        $adapter = EmailAdapterFactory::default();

        $this->assertInstanceOf(\App\Adapters\Email\Contracts\EmailAdapterInterface::class, $adapter);
    }

    /**
     * Testa se lista todos os provedores disponíveis
     */
    public function test_factory_lists_available_providers(): void
    {
        $providers = EmailAdapterFactory::getAvailableProviders();

        $this->assertIsArray($providers);
        $this->assertContains('smtp', $providers);
        $this->assertContains('ses', $providers);
        $this->assertContains('postmark', $providers);
        $this->assertContains('mailchimp', $providers);
    }
}






