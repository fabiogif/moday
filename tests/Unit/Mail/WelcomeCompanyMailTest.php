<?php

namespace Tests\Unit\Mail;

use App\Mail\WelcomeCompanyMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WelcomeCompanyMailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o e-mail de boas-vindas pode ser criado
     */
    public function test_welcome_company_mail_can_be_created(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Empresa Teste',
            'email' => 'empresa@teste.com',
        ]);

        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@teste.com',
            'tenant_id' => $tenant->id,
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Plano Básico',
            'price' => 99.90,
        ]);

        $mail = new WelcomeCompanyMail($tenant, $user, $plan);

        $this->assertInstanceOf(WelcomeCompanyMail::class, $mail);
        $this->assertEquals($tenant->id, $mail->tenant->id);
        $this->assertEquals($user->id, $mail->user->id);
        $this->assertEquals($plan->id, $mail->plan->id);
    }

    /**
     * Testa se o envelope do e-mail está correto
     */
    public function test_welcome_company_mail_envelope(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        $mail = new WelcomeCompanyMail($tenant, $user, $plan);
        $envelope = $mail->envelope();

        $this->assertEquals('Bem-vindo ao Alba Tec! 🎉', $envelope->subject);
        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals(config('mail.support_to'), $envelope->replyTo[0]->address);
    }

    /**
     * Testa se o conteúdo do e-mail contém os dados corretos
     */
    public function test_welcome_company_mail_content(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Empresa Teste',
            'slug' => 'empresa-teste',
        ]);

        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@teste.com',
            'tenant_id' => $tenant->id,
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Plano Premium',
        ]);

        $mail = new WelcomeCompanyMail($tenant, $user, $plan);
        $content = $mail->content();

        $this->assertEquals('emails.welcome-company', $content->view);
        $this->assertArrayHasKey('tenant', $content->with);
        $this->assertArrayHasKey('user', $content->with);
        $this->assertArrayHasKey('plan', $content->with);
        $this->assertArrayHasKey('loginUrl', $content->with);
    }

    /**
     * Testa se o e-mail pode ser enviado
     */
    public function test_welcome_company_mail_can_be_sent(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => 'empresa@teste.com',
        ]);

        $user = User::factory()->create([
            'email' => 'joao@teste.com',
            'tenant_id' => $tenant->id,
        ]);

        $plan = Plan::factory()->create();

        $mail = new WelcomeCompanyMail($tenant, $user, $plan);

        Mail::to($user->email)->send($mail);

        Mail::assertSent(WelcomeCompanyMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /**
     * Testa se o e-mail não tem anexos
     */
    public function test_welcome_company_mail_has_no_attachments(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        $mail = new WelcomeCompanyMail($tenant, $user, $plan);
        $attachments = $mail->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}

