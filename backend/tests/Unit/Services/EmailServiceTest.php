<?php

namespace Tests\Unit\Services;

use App\Services\EmailService;
use App\Adapters\Email\Contracts\EmailAdapterInterface;
use App\Mail\WelcomeCompanyMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o EmailService pode ser instanciado
     */
    public function test_email_service_can_be_instantiated(): void
    {
        $service = app(EmailService::class);

        $this->assertInstanceOf(EmailService::class, $service);
    }

    /**
     * Testa se o EmailService usa o adaptador padrão
     */
    public function test_email_service_uses_default_adapter(): void
    {
        $service = app(EmailService::class);

        $adapter = $service->getAdapter();

        $this->assertInstanceOf(EmailAdapterInterface::class, $adapter);
    }

    /**
     * Testa se o EmailService pode trocar de adaptador
     */
    public function test_email_service_can_change_adapter(): void
    {
        $service = app(EmailService::class);

        $mockAdapter = Mockery::mock(EmailAdapterInterface::class);
        $mockAdapter->shouldReceive('getProviderName')->andReturn('test-provider');
        $mockAdapter->shouldReceive('isConfigured')->andReturn(true);

        $service->setAdapter($mockAdapter);

        $this->assertEquals('test-provider', $service->getProviderName());
    }

    /**
     * Testa se o EmailService pode usar um provedor específico
     */
    public function test_email_service_can_use_specific_provider(): void
    {
        $service = app(EmailService::class);

        $service->useProvider('smtp');

        $this->assertEquals('smtp', $service->getProviderName());
    }

    /**
     * Testa se o EmailService envia e-mail corretamente
     */
    public function test_email_service_sends_email(): void
    {
        $mockAdapter = Mockery::mock(EmailAdapterInterface::class);
        $mockAdapter->shouldReceive('isConfigured')->andReturn(true);
        $mockAdapter->shouldReceive('send')->once()->andReturn(true);
        $mockAdapter->shouldReceive('getProviderName')->andReturn('test');

        $service = new EmailService($mockAdapter);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        $mailable = new WelcomeCompanyMail($tenant, $user, $plan);

        $result = $service->send('test@example.com', $mailable);

        $this->assertTrue($result);
    }

    /**
     * Testa se o EmailService lança exceção quando adaptador não está configurado
     */
    public function test_email_service_throws_exception_when_adapter_not_configured(): void
    {
        $mockAdapter = Mockery::mock(EmailAdapterInterface::class);
        $mockAdapter->shouldReceive('isConfigured')->andReturn(false);
        $mockAdapter->shouldReceive('getProviderName')->andReturn('test');

        $service = new EmailService($mockAdapter);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        $mailable = new WelcomeCompanyMail($tenant, $user, $plan);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('não está configurado');

        $service->send('test@example.com', $mailable);
    }
}






