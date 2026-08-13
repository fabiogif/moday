<?php

namespace Tests\Feature\Visit;

use App\Events\VisitCancelled;
use App\Events\VisitRescheduled;
use App\Events\VisitScheduled;
use App\Listeners\SendVisitCancelledEmail;
use App\Listeners\SendVisitCancelledWhatsApp;
use App\Listeners\SendVisitRescheduledEmail;
use App\Listeners\SendVisitRescheduledWhatsApp;
use App\Listeners\SendVisitScheduledEmail;
use App\Listeners\SendVisitScheduledWhatsApp;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use App\Mail\VisitNotificationMail;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VisitNotificationListenersTest extends TestCase
{
    use RefreshDatabase;

    private function makeVisit(): Visit
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $client = Client::factory()->create(['tenant_id' => $tenant->id]);

        return Visit::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'client_id' => $client->id]);
    }

    #[Test]
    public function visit_scheduled_queues_email_and_whatsapp_listeners(): void
    {
        Queue::fake();

        event(new VisitScheduled($this->makeVisit()));

        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $l) => $l->class === SendVisitScheduledEmail::class);
        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $l) => $l->class === SendVisitScheduledWhatsApp::class);
    }

    #[Test]
    public function visit_cancelled_queues_email_and_whatsapp_listeners(): void
    {
        Queue::fake();

        event(new VisitCancelled($this->makeVisit()));

        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $l) => $l->class === SendVisitCancelledEmail::class);
        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $l) => $l->class === SendVisitCancelledWhatsApp::class);
    }

    #[Test]
    public function visit_rescheduled_queues_email_and_whatsapp_listeners(): void
    {
        Queue::fake();

        $original = $this->makeVisit();
        $new = $this->makeVisit();

        event(new VisitRescheduled($new, $original));

        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $l) => $l->class === SendVisitRescheduledEmail::class);
        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $l) => $l->class === SendVisitRescheduledWhatsApp::class);
    }

    #[Test]
    public function it_actually_sends_the_notification_email_to_the_client(): void
    {
        Mail::fake();

        $visit = $this->makeVisit();
        $visit->client->update(['email' => 'cliente@example.com']);

        event(new VisitScheduled($visit));

        Mail::assertQueued(VisitNotificationMail::class, function (VisitNotificationMail $mail) use ($visit) {
            return $mail->hasTo('cliente@example.com') && $mail->visit->id === $visit->id && $mail->eventType === 'scheduled';
        });
    }

    #[Test]
    public function it_skips_email_silently_when_client_has_no_email(): void
    {
        Mail::fake();

        $visit = $this->makeVisit();
        $visit->client->update(['email' => null, 'contact_email' => null]);

        event(new VisitScheduled($visit));

        Mail::assertNotQueued(VisitNotificationMail::class);
    }
}
