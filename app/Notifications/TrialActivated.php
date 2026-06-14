<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialActivated extends Notification implements ShouldQueue
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
        $expiresAt = $this->tenant->trial_expires_at?->format('d/m/Y');
        
        return (new MailMessage)
            ->subject('🎉 Bem-vindo ao Alba Tec! Seu teste de 7 dias começou')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Seja bem-vindo ao **Alba Tec**! 🎉')
            ->line('Seu período de teste gratuito de **7 dias** já está ativo.')
            ->line('**Válido até:** ' . $expiresAt)
            ->line('Durante este período, você terá acesso completo a todos os recursos:')
            ->line('✓ Gestão completa de pedidos')
            ->line('✓ Controle de estoque')
            ->line('✓ Relatórios detalhados')
            ->line('✓ Programa de fidelidade')
            ->line('✓ E muito mais!')
            ->action('Começar Agora', url('/dashboard'))
            ->line('**Dica:** Aproveite estes 7 dias para explorar todas as funcionalidades.')
            ->line('Se precisar de ajuda, nossa equipe está sempre disponível!')
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
            'type' => 'trial_activated',
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'days_remaining' => 7,
            'started_at' => $this->tenant->trial_started_at?->format('Y-m-d H:i:s'),
            'expires_at' => $this->tenant->trial_expires_at?->format('Y-m-d H:i:s'),
            'message' => 'Seu período de teste de 7 dias foi ativado!',
            'action_url' => url('/dashboard'),
        ];
    }
}

