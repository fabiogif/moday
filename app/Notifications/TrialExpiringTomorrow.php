<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringTomorrow extends Notification implements ShouldQueue
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
            ->subject('🚨 URGENTE: Seu período de teste expira amanhã!')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('**Atenção!** Seu período de teste gratuito termina amanhã.')
            ->line('Não perca o acesso ao Alba Tec! Faça upgrade agora e continue aproveitando todos os recursos.')
            ->action('Fazer Upgrade Agora', url('/plans'))
            ->line('O que acontece após a expiração:')
            ->line('❌ Você não poderá mais acessar o sistema')
            ->line('❌ Seus dados ficam preservados por 30 dias')
            ->line('✅ Você pode reativar a qualquer momento')
            ->line('**Não deixe para a última hora!** Escolha seu plano agora e mantenha seu negócio funcionando.')
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
            'type' => 'trial_expiring_tomorrow',
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'days_remaining' => 1,
            'expires_at' => $this->tenant->trial_expires_at?->format('Y-m-d H:i:s'),
            'message' => 'Seu período de teste expira amanhã!',
            'action_url' => url('/plans'),
            'urgent' => true,
        ];
    }
}

