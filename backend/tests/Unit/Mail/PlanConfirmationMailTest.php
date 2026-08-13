<?php

namespace Tests\Unit\Mail;

use App\Mail\PlanConfirmationMail;
use App\Models\Plan;
use App\Models\PlanMigration;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlanConfirmationMailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o e-mail de confirmação de plano pode ser criado
     */
    public function test_plan_confirmation_mail_can_be_created(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Empresa Teste',
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Plano Premium',
            'price' => 199.90,
        ]);

        $mail = new PlanConfirmationMail($tenant, $plan);

        $this->assertInstanceOf(PlanConfirmationMail::class, $mail);
        $this->assertEquals($tenant->id, $mail->tenant->id);
        $this->assertEquals($plan->id, $mail->plan->id);
        $this->assertFalse($mail->isMigration);
    }

    /**
     * Testa se o e-mail de migração de plano pode ser criado
     */
    public function test_plan_confirmation_mail_with_migration(): void
    {
        $tenant = Tenant::factory()->create();

        $oldPlan = Plan::factory()->create([
            'name' => 'Plano Básico',
            'price' => 99.90,
        ]);

        $newPlan = Plan::factory()->create([
            'name' => 'Plano Premium',
            'price' => 199.90,
        ]);

        $migration = PlanMigration::factory()->create([
            'tenant_id' => $tenant->id,
            'from_plan_id' => $oldPlan->id,
            'to_plan_id' => $newPlan->id,
        ]);

        $mail = new PlanConfirmationMail($tenant, $newPlan, $oldPlan, $migration);

        $this->assertTrue($mail->isMigration);
        $this->assertEquals($oldPlan->id, $mail->oldPlan->id);
        $this->assertEquals($migration->id, $mail->migration->id);
    }

    /**
     * Testa se o envelope do e-mail está correto para confirmação
     */
    public function test_plan_confirmation_mail_envelope_for_confirmation(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['name' => 'Plano Básico']);

        $mail = new PlanConfirmationMail($tenant, $plan);
        $envelope = $mail->envelope();

        $this->assertStringContainsString('Confirmação de plano', $envelope->subject);
        $this->assertStringContainsString('Plano Básico', $envelope->subject);
        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals(config('mail.support_to'), $envelope->replyTo[0]->address);
    }

    /**
     * Testa se o envelope do e-mail está correto para migração
     */
    public function test_plan_confirmation_mail_envelope_for_migration(): void
    {
        $tenant = Tenant::factory()->create();
        $oldPlan = Plan::factory()->create(['name' => 'Plano Básico']);
        $newPlan = Plan::factory()->create(['name' => 'Plano Premium']);

        $mail = new PlanConfirmationMail($tenant, $newPlan, $oldPlan);
        $envelope = $mail->envelope();

        $this->assertStringContainsString('Plano atualizado', $envelope->subject);
        $this->assertStringContainsString('Plano Premium', $envelope->subject);
    }

    /**
     * Testa se o conteúdo do e-mail contém os dados corretos
     */
    public function test_plan_confirmation_mail_content(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Empresa Teste',
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Plano Premium',
            'price' => 199.90,
        ]);

        $mail = new PlanConfirmationMail($tenant, $plan);
        $content = $mail->content();

        $this->assertEquals('emails.plan-confirmation', $content->view);
        $this->assertArrayHasKey('tenant', $content->with);
        $this->assertArrayHasKey('plan', $content->with);
        $this->assertArrayHasKey('oldPlan', $content->with);
        $this->assertArrayHasKey('migration', $content->with);
        $this->assertArrayHasKey('isMigration', $content->with);
        $this->assertArrayHasKey('dashboardUrl', $content->with);
    }

    /**
     * Testa se o e-mail pode ser enviado
     */
    public function test_plan_confirmation_mail_can_be_sent(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => 'empresa@teste.com',
        ]);

        $plan = Plan::factory()->create();

        $mail = new PlanConfirmationMail($tenant, $plan);

        Mail::to($tenant->email)->send($mail);

        Mail::assertSent(PlanConfirmationMail::class, function ($mail) use ($tenant) {
            return $mail->hasTo($tenant->email);
        });
    }

    /**
     * Testa se o e-mail não tem anexos
     */
    public function test_plan_confirmation_mail_has_no_attachments(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();

        $mail = new PlanConfirmationMail($tenant, $plan);
        $attachments = $mail->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}

