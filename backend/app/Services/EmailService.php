<?php

namespace App\Services;

use App\Adapters\Email\Contracts\EmailAdapterInterface;
use App\Adapters\Email\EmailAdapterFactory;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de e-mail que utiliza adaptadores
 * 
 * Abstrai o uso de diferentes provedores de e-mail através do padrão Adapter
 */
class EmailService
{
    private EmailAdapterInterface $adapter;

    /**
     * Construtor - inicializa o adaptador padrão
     */
    public function __construct(?EmailAdapterInterface $adapter = null)
    {
        $this->adapter = $adapter ?? EmailAdapterFactory::default();
    }

    /**
     * Define o adaptador a ser usado
     *
     * @param EmailAdapterInterface $adapter
     * @return self
     */
    public function setAdapter(EmailAdapterInterface $adapter): self
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Usa um provedor específico
     *
     * @param string $provider Nome do provedor (smtp, ses, postmark, mailchimp)
     * @return self
     */
    public function useProvider(string $provider): self
    {
        $this->adapter = EmailAdapterFactory::create($provider);
        return $this;
    }

    /**
     * Envia um e-mail usando o adaptador configurado
     *
     * @param string|array $to Destinatário(s)
     * @param Mailable $mailable Instância do Mailable
     * @return bool True se enviado com sucesso
     * @throws \Exception Em caso de erro
     */
    public function send(string|array $to, Mailable $mailable): bool
    {
        try {
            if (!$this->adapter->isConfigured()) {
                throw new \RuntimeException(
                    "Provedor de e-mail '{$this->adapter->getProviderName()}' não está configurado corretamente."
                );
            }

            return $this->adapter->send($to, $mailable);
        } catch (\Exception $e) {
            Log::error('EmailService: Erro ao enviar e-mail', [
                'provider' => $this->adapter->getProviderName(),
                'to' => is_array($to) ? implode(', ', $to) : $to,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Envia e-mail para múltiplos destinatários
     *
     * @param array $to Lista de destinatários
     * @param Mailable $mailable Instância do Mailable
     * @return array Resultado ['sent' => int, 'failed' => int]
     */
    public function sendBulk(array $to, Mailable $mailable): array
    {
        try {
            if (!$this->adapter->isConfigured()) {
                throw new \RuntimeException(
                    "Provedor de e-mail '{$this->adapter->getProviderName()}' não está configurado corretamente."
                );
            }

            return $this->adapter->sendBulk($to, $mailable);
        } catch (\Exception $e) {
            Log::error('EmailService: Erro ao enviar e-mails em massa', [
                'provider' => $this->adapter->getProviderName(),
                'total_recipients' => count($to),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Retorna o adaptador atual
     *
     * @return EmailAdapterInterface
     */
    public function getAdapter(): EmailAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Retorna o nome do provedor atual
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->adapter->getProviderName();
    }
}






