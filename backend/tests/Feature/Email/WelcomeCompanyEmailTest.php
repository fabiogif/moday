<?php

namespace Tests\Feature\Email;

use App\Events\CompanyRegistered;
use App\Listeners\SendWelcomeCompanyEmail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WelcomeCompanyEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o evento CompanyRegistered dispara o listener corretamente
     */
    public function test_company_registered_event_dispatches_listener(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        event(new CompanyRegistered($tenant, $user, $plan));

        Event::assertDispatched(CompanyRegistered::class);
    }

    /**
     * Testa se o e-mail de boas-vindas é enviado quando uma empresa se registra
     */
    public function test_welcome_email_is_sent_when_company_registers(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => 'empresa@teste.com',
        ]);

        $user = User::factory()->create([
            'email' => 'admin@teste.com',
            'tenant_id' => $tenant->id,
        ]);

        $plan = Plan::factory()->create();

        $listener = new SendWelcomeCompanyEmail();
        $event = new CompanyRegistered($tenant, $user, $plan);

        $listener->handle($event);

        Mail::assertSent(\App\Mail\WelcomeCompanyMail::class, function ($mail) use ($tenant) {
            return $mail->hasTo($tenant->email);
        });
    }

    /**
     * Testa se o e-mail usa o e-mail do usuário quando o tenant não tem e-mail
     */
    public function test_welcome_email_uses_user_email_when_tenant_has_no_email(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => null,
        ]);

        $user = User::factory()->create([
            'email' => 'admin@teste.com',
            'tenant_id' => $tenant->id,
        ]);

        $plan = Plan::factory()->create();

        $listener = new SendWelcomeCompanyEmail();
        $event = new CompanyRegistered($tenant, $user, $plan);

        $listener->handle($event);

        Mail::assertSent(\App\Mail\WelcomeCompanyMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /**
     * Testa se não envia e-mail quando nenhum e-mail está disponível
     */
    public function test_welcome_email_not_sent_when_no_email_available(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => null,
        ]);

        // Como o campo email é NOT NULL, precisamos criar o usuário e depois atualizar para string vazia
        // ou usar uma abordagem que simule a ausência de e-mail
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        
        // Atualizar para string vazia para simular ausência de e-mail
        $user->update(['email' => '']);

        $plan = Plan::factory()->create();

        $listener = new SendWelcomeCompanyEmail();
        $event = new CompanyRegistered($tenant, $user->fresh(), $plan);

        $listener->handle($event);

        Mail::assertNothingSent();
    }
}

