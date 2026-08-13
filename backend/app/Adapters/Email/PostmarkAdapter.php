<?php

namespace App\Adapters\Email;

use App\Adapters\Email\Contracts\EmailAdapterInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Adaptador Postmark
 * 
 * Utiliza o serviço Postmark para envio de e-mails transacionais
 */
class PostmarkAdapter implements EmailAdapterInterface
{
    /**
     * Envia um e-mail usando Postmark
     */
    public function send(string|array $to, Mailable $mailable): bool
    {
        try {
            // Usar o mailer 'postmark' configurado no Laravel
            Mail::mailer('postmark')->to($to)->send($mailable);
            
            Log::info('PostmarkAdapter: E-mail enviado com sucesso via Postmark', [
                'to' => is_array($to) ? implode(', ', $to) : $to,
                'provider' => $this->getProviderName(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('PostmarkAdapter: Erro ao enviar e-mail via Postmark', [
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
                Log::warning('PostmarkAdapter: Falha ao enviar para destinatário', [
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
     * Verifica se o Postmark está configurado
     */
    public function isConfigured(): bool
    {
        $token = config('services.postmark.token');
        
        return !empty($token);
    }

    /**
     * Retorna o nome do provedor
     */
    public function getProviderName(): string
    {
        return 'postmark';
    }
}






