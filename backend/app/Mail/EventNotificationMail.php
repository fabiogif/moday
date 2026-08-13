<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public Client $client;
    public string $tenantName;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, Client $client, string $tenantName)
    {
        $this->event = $event;
        $this->client = $client;
        $this->tenantName = $tenantName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->getSubjectByType();
        $template = $this->getTemplateByType();

        return $this->subject($subject)
                    ->view($template)
                    ->with([
                        'event' => $this->event,
                        'client' => $this->client,
                        'tenantName' => $this->tenantName,
                    ]);
    }

    /**
     * Get email subject based on event type
     */
    private function getSubjectByType(): string
    {
        return match($this->event->type) {
            'promocao' => "🎉 {$this->tenantName} - Nova Promoção: {$this->event->title}",
            'aviso' => "📢 {$this->tenantName} - Aviso Importante: {$this->event->title}",
            'outro' => "📅 {$this->tenantName} - {$this->event->title}",
            default => "{$this->tenantName} - {$this->event->title}",
        };
    }

    /**
     * Get email template based on event type
     */
    private function getTemplateByType(): string
    {
        return match($this->event->type) {
            'promocao' => 'emails.events.promocao',
            'aviso' => 'emails.events.aviso',
            'outro' => 'emails.events.outro',
            default => 'emails.events.outro',
        };
    }
}

