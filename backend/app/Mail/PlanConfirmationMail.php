<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\Plan;
use App\Models\PlanMigration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de confirmação enviado quando uma empresa confirma a adesão a um plano
 * ou migra para um novo plano
 */
class PlanConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Tenant $tenant;
    public Plan $plan;
    public ?Plan $oldPlan;
    public ?PlanMigration $migration;
    public bool $isMigration;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Tenant $tenant,
        Plan $plan,
        ?Plan $oldPlan = null,
        ?PlanMigration $migration = null
    ) {
        $this->tenant = $tenant;
        $this->plan = $plan;
        $this->oldPlan = $oldPlan;
        $this->migration = $migration;
        $this->isMigration = $oldPlan !== null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isMigration
            ? "Plano atualizado: {$this->plan->name}"
            : "Confirmação de plano: {$this->plan->name}";

        return new Envelope(
            subject: $subject,
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
            view: 'emails.plan-confirmation',
            with: [
                'tenant' => $this->tenant,
                'plan' => $this->plan,
                'oldPlan' => $this->oldPlan,
                'migration' => $this->migration,
                'isMigration' => $this->isMigration,
                'dashboardUrl' => config('app.frontend_url', 'http://localhost:3000') . '/dashboard',
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






