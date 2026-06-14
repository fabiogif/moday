<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiredToday extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Tenant $tenant
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔒 Seu período de teste expirou')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Seu período de teste gratuito de 7 dias terminou hoje.')
            ->line('Esperamos que você tenha aproveitado para conhecer todos os recursos do Alba Tec!')
            ->line('Para continuar usando o sistema, escolha um de nossos planos:')
            ->action('Escolher Plano e Reativar', url('/plans'))
            ->line('**Seus dados estão seguros!**')
            ->line('Todas as suas informações ficam preservadas por 30 dias.')
            ->line('Você pode reativar sua conta a qualquer momento.')
            ->line('Tem dúvidas ou precisa de ajuda? Nossa equipe está pronta para atendê-lo.')
            ->salutation('Equipe Alba Tec');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_expired',
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'days_remaining' => 0,
            'expired_at' => $this->tenant->trial_expires_at?->format('Y-m-d H:i:s'),
            'message' => 'Seu período de teste expirou',
            'action_url' => url('/plans'),
            'critical' => true,
        ];
    }
}

