<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail em massa enviado pelo painel administrativo para empresas
 */
class AdminBulkEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Tenant $tenant;
    public string $emailSubject;
    public string $emailBody;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $message, Tenant $tenant)
    {
        $this->emailSubject = $subject;
        $this->emailBody = $message;
        $this->tenant = $tenant;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-bulk',
            with: [
                'tenant' => $this->tenant,
                'message' => $this->emailBody,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}






