<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $appName,
        public string $mailDriver,
        public string $host,
        public int $port,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->appName}] Teste de envio de e-mail",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.test',
            with: [
                'appName' => $this->appName,
                'mailer' => $this->mailDriver,
                'host' => $this->host,
                'port' => $this->port,
                'sentAt' => now()->format('d/m/Y H:i:s'),
            ],
        );
    }
}
