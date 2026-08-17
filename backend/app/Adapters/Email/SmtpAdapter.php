<?php

namespace App\Adapters\Email;

use App\Adapters\Email\Contracts\EmailAdapterInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Adaptador SMTP nativo do Laravel
 * 
 * Utiliza o sistema de e-mail nativo do Laravel com configuração SMTP
 */
class SmtpAdapter implements EmailAdapterInterface
{
    /**
     * Envia um e-mail usando SMTP
     */
    public function send(string|array $to, Mailable $mailable): bool
    {
        try {
            Mail::to($to)->send($mailable);
            
            Log::info('SmtpAdapter: E-mail enviado com sucesso', [
                'to' => is_array($to) ? implode(', ', $to) : $to,
                'provider' => $this->getProviderName(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SmtpAdapter: Erro ao enviar e-mail', [
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
                Log::warning('SmtpAdapter: Falha ao enviar para destinatário', [
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
     * Verifica se o SMTP está configurado
     */
    public function isConfigured(): bool
    {
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        
        return !empty($host) && !empty($port);
    }

    /**
     * Retorna o nome do provedor
     */
    public function getProviderName(): string
    {
        return 'smtp';
    }
}






