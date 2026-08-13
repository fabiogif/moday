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
            ->subject('Seu período de teste expirou')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Seu período de teste gratuito de 7 dias terminou hoje.')
            ->line("Para continuar usando o {$brand}, escolha um plano:")
            ->action('Escolher plano e reativar', $plansUrl)
            ->line('**Seus dados estão seguros.**')
            ->line('As informações ficam preservadas por 30 dias e você pode reativar a qualquer momento.')
            ->salutation('Equipe ' . $brand);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_expired',
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'days_remaining' => 0,
            'expired_at' => $this->tenant->trial_expires_at?->format('Y-m-d H:i:s'),
            'message' => 'Seu período de teste expirou',
            'action_url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/subscribe',
            'critical' => true,
        ];
    }
}
