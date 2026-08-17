<?php

namespace App\Listeners;

use App\Events\VisitRescheduled;
use App\Mail\VisitNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendVisitRescheduledEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(VisitRescheduled $event): void
    {
        $client = $event->visit->loadMissing('client')->client;
        if (!$client) {
            return;
        }

        $email = $client->contact_email ?: $client->email;
        if (empty($email)) {
            return;
        }

        Mail::to($email)->queue(new VisitNotificationMail($event->visit, 'rescheduled', $event->originalVisit));
    }
}
