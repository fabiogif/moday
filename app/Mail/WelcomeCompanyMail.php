<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de boas-vindas enviado quando uma empresa se cadastra na plataforma
 */
class WelcomeCompanyMail extends Mailable
{
    use Queueable, SerializesModels;

    public Tenant $tenant;
    public User $user;
    public Plan $plan;

    /**
     * Create a new message instance.
     */
    public function __construct(Tenant $tenant, User $user, Plan $plan)
    {
        $this->tenant = $tenant;
        $this->user = $user;
        $this->plan = $plan;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao Alba Tec! 🎉',
            replyTo: [
                new Address(config('mail.support_to'), config('mail.from.name')),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-company',
            with: [
                'tenant' => $this->tenant,
                'user' => $this->user,
                'plan' => $this->plan,
                'loginUrl' => config('app.frontend_url', 'http://localhost:3000') . '/login',
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






