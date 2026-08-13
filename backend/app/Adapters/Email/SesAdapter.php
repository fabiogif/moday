<?php

namespace App\Adapters\Email;

use App\Adapters\Email\Contracts\EmailAdapterInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Adaptador AWS SES (Simple Email Service)
 * 
 * Utiliza o serviço AWS SES para envio de e-mails transacionais
 */
class SesAdapter implements EmailAdapterInterface
{
    /**
     * Envia um e-mail usando AWS SES
     */
    public function send(string|array $to, Mailable $mailable): bool
    {
        try {
            // Usar o mailer 'ses' configurado no Laravel
            Mail::mailer('ses')->to($to)->send($mailable);
            
            Log::info('SesAdapter: E-mail enviado com sucesso via AWS SES', [
                'to' => is_array($to) ? implode(', ', $to) : $to,
                'provider' => $this->getProviderName(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SesAdapter: Erro ao enviar e-mail via AWS SES', [
                'to' => is_array($to) ? implode(', ', $to) : $to,
                'error' => $e->getMessage(),
                'provider' => $this->getProviderName(),
            ]);

            throw $e;
        }
    }

    /**
     * Envia e-mail para múltiplos destinatários
     */
    public function sendBulk(array $to, Mailable $mailable): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($to as $recipient) {
            try {
                $this->send($recipient, $mailable);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::warning('SesAdapter: Falha ao enviar para destinatário', [
                    'to' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    /**
     * Verifica se o AWS SES está configurado
     */
    public function isConfigured(): bool
    {
        $key = config('services.ses.key');
        $secret = config('services.ses.secret');
        $region = config('services.ses.region');
        
        return !empty($key) && !empty($secret) && !empty($region);
    }

    /**
     * Retorna o nome do provedor
     */
    public function getProviderName(): string
    {
        return 'aws-ses';
    }
}






