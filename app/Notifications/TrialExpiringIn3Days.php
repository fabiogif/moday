<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringIn3Days extends Notification implements ShouldQueue
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
            ->subject('⚠️ Seu período de teste expira em 3 dias')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Seu período de teste gratuito de 7 dias está chegando ao fim.')
            ->line('**Você ainda tem 3 dias** para aproveitar todos os recursos do Alba Tec.')
            ->line('Para continuar utilizando o sistema sem interrupções, escolha um de nossos planos.')
            ->action('Ver Planos e Fazer Upgrade', url('/plans'))
            ->line('Benefícios de assinar agora:')
            ->line('✓ Acesso ilimitado a todos os recursos')
            ->line('✓ Suporte prioritário')
            ->line('✓ Atualizações automáticas')
            ->line('✓ Backup diário dos seus dados')
            ->line('Se tiver alguma dúvida, estamos aqui para ajudar!')
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
            'type' => 'trial_expiring_soon',
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'days_remaining' => 3,
            'expires_at' => $this->tenant->trial_expires_at?->format('Y-m-d H:i:s'),
            'message' => 'Seu período de teste expira em 3 dias',
            'action_url' => url('/plans'),
        ];
    }
}

