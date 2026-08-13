<?php

namespace App\Adapters\Email;

use App\Adapters\Email\Contracts\EmailAdapterInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Adaptador Mailchimp (Mandrill)
 * 
 * Utiliza a API do Mailchimp/Mandrill para envio de e-mails transacionais
 */
class MailchimpAdapter implements EmailAdapterInterface
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.mailchimp.api_key') ?? '';
        $this->apiUrl = config('services.mailchimp.api_url') ?? 'https://mandrillapp.com/api/1.0';
    }

    /**
     * Envia um e-mail usando Mailchimp/Mandrill
     */
    public function send(string|array $to, Mailable $mailable): bool
    {
        try {
            $recipients = is_array($to) ? $to : [$to];
            
            // Renderizar o conteúdo do e-mail usando a view do Mailable
            $content = $mailable->content();
            $envelope = $mailable->envelope();
            
            // Renderizar a view Blade
            $view = $content->view;
            $data = $content->with ?? [];
            $html = view($view, $data)->render();
            
            $message = [
                'html' => $html,
                'subject' => $envelope->subject,
                'from_email' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'to' => array_map(function ($email) {
                    return ['email' => $email, 'type' => 'to'];
                }, $recipients),
            ];

            $response = Http::post("{$this->apiUrl}/messages/send", [
                'key' => $this->apiKey,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('MailchimpAdapter: E-mail enviado com sucesso via Mailchimp', [
                    'to' => implode(', ', $recipients),
                    'provider' => $this->getProviderName(),
                ]);

                return true;
            }

            throw new \Exception('Mailchimp API error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('MailchimpAdapter: Erro ao enviar e-mail via Mailchimp', [
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
        try {
            // Mailchimp suporta múltiplos destinatários em uma única requisição
            $this->send($to, $mailable);
            
            return [
                'sent' => count($to),
                'failed' => 0,
            ];
        } catch (\Exception $e) {
            // Em caso de falha, tentar enviar individualmente
            $sent = 0;
            $failed = 0;

            foreach ($to as $recipient) {
                try {
                    $this->send($recipient, $mailable);
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning('MailchimpAdapter: Falha ao enviar para destinatário', [
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
    }

    /**
     * Verifica se o Mailchimp está configurado
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Retorna o nome do provedor
     */
    public function getProviderName(): string
    {
        return 'mailchimp';
    }
}

