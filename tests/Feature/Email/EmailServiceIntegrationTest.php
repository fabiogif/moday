<?php

namespace Tests\Feature\Email;

use App\Services\EmailService;
use App\Mail\WelcomeCompanyMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o EmailService pode ser resolvido do container
     */
    public function test_email_service_can_be_resolved_from_container(): void
    {
        $service = app(EmailService::class);

        $this->assertInstanceOf(EmailService::class, $service);
    }

    /**
     * Testa se o EmailService envia e-mail usando adaptador padrão
     */
    public function test_email_service_sends_email_with_default_adapter(): void
    {
        Mail::fake();

        $service = app(EmailService::class);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        $mailable = new WelcomeCompanyMail($tenant, $user, $plan);

        $result = $service->send('test@example.com', $mailable);

        $this->assertTrue($result);
        Mail::assertSent(WelcomeCompanyMail::class);
    }

    /**
     * Testa se o EmailService pode trocar de provedor
     */
    public function test_email_service_can_change_provider(): void
    {
        $service = app(EmailService::class);

        $service->useProvider('smtp');

        $this->assertEquals('smtp', $service->getProviderName());
    }

    /**
     * Testa se o EmailService envia e-mail em massa
     */
    public function test_email_service_sends_bulk_email(): void
    {
        Mail::fake();

        $service = app(EmailService::class);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        $mailable = new WelcomeCompanyMail($tenant, $user, $plan);

        $recipients = ['test1@example.com', 'test2@example.com'];

        $result = $service->sendBulk($recipients, $mailable);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sent', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertEquals(2, $result['sent']);
    }

    /**
     * Testa se o EmailService retorna o adaptador atual
     */
    public function test_email_service_returns_current_adapter(): void
    {
        $service = app(EmailService::class);

        $adapter = $service->getAdapter();

        $this->assertInstanceOf(\App\Adapters\Email\Contracts\EmailAdapterInterface::class, $adapter);
    }
}






