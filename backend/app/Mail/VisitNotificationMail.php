<?php

namespace App\Mail;

use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Um Mailable único para os três eventos de visita (scheduled/cancelled/rescheduled) —
 * mesma estrutura de e-mail (cabeçalho do tenant, dados da visita), só o texto e o
 * assunto mudam por $eventType. Evita triplicar Mailable+Blade quase idênticos
 * (SaleOrderConfirmationMail é 1:1 porque só existe um evento; aqui são três).
 */
class VisitNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public const SUBJECTS = [
        'scheduled' => 'Visita agendada',
        'cancelled' => 'Visita cancelada',
        'rescheduled' => 'Visita reagendada',
    ];

    public function __construct(
        public readonly Visit $visit,
        public readonly string $eventType,
        public readonly ?Visit $originalVisit = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $tenantName = $this->visit->tenant?->name ?? config('app.name');
        $subjectLabel = self::SUBJECTS[$this->eventType] ?? 'Atualização da visita';

        return new Envelope(
            subject: "{$subjectLabel} — {$tenantName}",
            replyTo: [
                new Address(config('mail.support_to', config('mail.from.address')), $tenantName),
            ],
        );
    }

    public function content(): Content
    {
        $visit = $this->visit->loadMissing(['client', 'user', 'tenant']);
        $tenantName = $visit->tenant?->name ?? config('app.name');
        $clientName = $visit->client?->trade_name ?? $visit->client?->company_name ?? $visit->client?->name ?? 'Cliente';

        return new Content(
            view: 'emails.visit-notification',
            with: [
                'visit' => $visit,
                'originalVisit' => $this->originalVisit,
                'eventType' => $this->eventType,
                'tenantName' => $tenantName,
                'clientName' => $clientName,
            ],
        );
    }
}
