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

    public function __construct(
        public Tenant $tenant
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresAt = $this->tenant->trial_expires_at?->format('d/m/Y');
        $dashboardUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . '/dashboard';
        $brand = config('mail.brand.name', 'DistribTec');

        return (new MailMessage)
            ->subject("Bem-vindo ao {$brand} — seu teste de 7 dias começou")
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line("Seja bem-vindo ao **{$brand}**.")
            ->line('Seu período de teste gratuito de **7 dias** já está ativo.')
            ->line('**Válido até:** ' . $expiresAt)
            ->line('Durante este período, você terá acesso aos recursos da plataforma:')
            ->line('• Gestão de pedidos e estoque')
            ->line('• Relatórios e operação de campo')
            ->line('• Controles financeiros e comerciais')
            ->action('Começar agora', $dashboardUrl)
            ->line('Aproveite os 7 dias para explorar as funcionalidades. Se precisar de ajuda, nossa equipe está disponível.')
            ->salutation('Equipe ' . $brand);
    }

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
            'action_url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/dashboard',
        ];
    }
}
