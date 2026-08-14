<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirme seu e-mail — ' . config('app.name', 'Alba Tec'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification-code',
            with: [
                'userName' => $this->user->name,
                'code' => $this->code,
                'expiresMinutes' => 15,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
