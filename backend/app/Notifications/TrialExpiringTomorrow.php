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
            ->subject('Seu período de teste expira amanhã')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Seu período de teste gratuito termina amanhã.')
            ->line("Para manter o acesso ao {$brand}, escolha um plano agora.")
            ->action('Escolher plano', $plansUrl)
            ->line('Após a expiração:')
            ->line('• O acesso ao sistema fica bloqueado')
            ->line('• Seus dados ficam preservados por 30 dias')
            ->line('• Você pode reativar a qualquer momento')
            ->salutation('Equipe ' . $brand);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_expiring_tomorrow',
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'days_remaining' => 1,
            'expires_at' => $this->tenant->trial_expires_at?->format('Y-m-d H:i:s'),
            'message' => 'Seu período de teste expira amanhã!',
            'action_url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/subscribe',
            'urgent' => true,
        ];
    }
}
