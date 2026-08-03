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

    public function __construct(
        public Tenant $tenant
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = config('mail.brand.name', 'DistribTec');
        $plansUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . '/subscribe';

        return (new MailMessage)
            ->subject('Seu período de teste expira em 3 dias')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Seu período de teste gratuito de 7 dias está chegando ao fim.')
            ->line("**Você ainda tem 3 dias** para aproveitar os recursos do {$brand}.")
            ->line('Para continuar sem interrupções, escolha um plano.')
            ->action('Ver planos', $plansUrl)
            ->line('Ao assinar, você mantém acesso contínuo, suporte e atualizações.')
            ->salutation('Equipe ' . $brand);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_expiring_soon',
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'days_remaining' => 3,
            'expires_at' => $this->tenant->trial_expires_at?->format('Y-m-d H:i:s'),
            'message' => 'Seu período de teste expira em 3 dias',
            'action_url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/subscribe',
        ];
    }
}
