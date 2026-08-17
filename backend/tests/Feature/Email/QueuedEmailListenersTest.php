<?php

namespace Tests\Feature\Email;

use App\Events\CompanyRegistered;
use App\Events\PlanConfirmed;
use App\Listeners\SendPlanConfirmationEmail;
use App\Listeners\SendWelcomeCompanyEmail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedEmailListenersTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_registered_queues_welcome_email_listener(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create();

        event(new CompanyRegistered($tenant, $user, $plan));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener) {
            return $listener->class === SendWelcomeCompanyEmail::class;
        });
    }

    public function test_plan_confirmed_queues_confirmation_email_listener(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();

        event(new PlanConfirmed($tenant, $plan));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener) {
            return $listener->class === SendPlanConfirmationEmail::class;
        });
    }
}
