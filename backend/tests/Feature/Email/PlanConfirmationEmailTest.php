<?php

namespace Tests\Feature\Email;

use App\Events\PlanConfirmed;
use App\Listeners\SendPlanConfirmationEmail;
use App\Models\Plan;
use App\Models\PlanMigration;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlanConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o evento PlanConfirmed dispara o listener corretamente
     */
    public function test_plan_confirmed_event_dispatches_listener(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();

        event(new PlanConfirmed($tenant, $plan));

        Event::assertDispatched(PlanConfirmed::class);
    }

    /**
     * Testa se o e-mail de confirmação é enviado quando um plano é confirmado
     */
    public function test_plan_confirmation_email_is_sent_when_plan_confirmed(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => 'empresa@teste.com',
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Plano Premium',
        ]);

        $listener = new SendPlanConfirmationEmail();
        $event = new PlanConfirmed($tenant, $plan);

        $listener->handle($event);

        Mail::assertSent(\App\Mail\PlanConfirmationMail::class, function ($mail) use ($tenant) {
            return $mail->hasTo($tenant->email);
        });
    }

    /**
     * Testa se o e-mail de migração é enviado corretamente
     */
    public function test_plan_confirmation_email_for_migration(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => 'empresa@teste.com',
        ]);

        $oldPlan = Plan::factory()->create(['name' => 'Plano Básico']);
        $newPlan = Plan::factory()->create(['name' => 'Plano Premium']);

        $migration = PlanMigration::factory()->create([
            'tenant_id' => $tenant->id,
            'from_plan_id' => $oldPlan->id,
            'to_plan_id' => $newPlan->id,
        ]);

        $listener = new SendPlanConfirmationEmail();
        $event = new PlanConfirmed($tenant, $newPlan, $oldPlan, $migration);

        $listener->handle($event);

        Mail::assertSent(\App\Mail\PlanConfirmationMail::class, function ($mail) {
            return $mail->isMigration === true;
        });
    }

    /**
     * Testa se o e-mail usa o e-mail do admin quando o tenant não tem e-mail
     */
    public function test_plan_confirmation_email_uses_admin_email_when_tenant_has_no_email(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => null,
        ]);

        $plan = Plan::factory()->create();

        // Criar perfil Admin com nome exato "Admin" (case-sensitive)
        $adminProfile = Profile::factory()->create([
            'name' => 'Admin',
            'description' => 'Administrador do sistema',
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'email' => 'admin@teste.com',
            'tenant_id' => $tenant->id,
        ]);

        $adminUser->profiles()->attach($adminProfile->id);

        $listener = new SendPlanConfirmationEmail();
        $event = new PlanConfirmed($tenant->fresh(), $plan);

        $listener->handle($event);

        Mail::assertSent(\App\Mail\PlanConfirmationMail::class, function ($mail) use ($adminUser) {
            return $mail->hasTo($adminUser->email);
        });
    }

    /**
     * Testa se não envia e-mail quando nenhum e-mail está disponível
     */
    public function test_plan_confirmation_email_not_sent_when_no_email_available(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create([
            'email' => null,
        ]);

        $plan = Plan::factory()->create();

        // Criar usuário sem perfil Admin
        User::factory()->create([
            'email' => 'user@teste.com',
            'tenant_id' => $tenant->id,
        ]);

        $listener = new SendPlanConfirmationEmail();
        $event = new PlanConfirmed($tenant, $plan);

        $listener->handle($event);

        Mail::assertNothingSent();
    }
}

