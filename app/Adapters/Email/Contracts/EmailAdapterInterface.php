<?php

namespace App\Adapters\Email\Contracts;

use Illuminate\Mail\Mailable;

/**
 * Interface para adaptadores de e-mail
 * 
 * Define o contrato que todos os adaptadores de e-mail devem seguir,
 * permitindo trocar facilmente entre diferentes provedores (SMTP, SES, Postmark, Mailchimp, etc.)
 */
interface EmailAdapterInterface
{
    /**
     * Envia um e-mail usando o adaptador específico
     *
     * @param string|array $to Destinatário(s) do e-mail
     * @param Mailable $mailable Instância do Mailable a ser enviado
     * @return bool True se o e-mail foi enviado com sucesso, false caso contrário
     * @throws \Exception Em caso de erro no envio
     */
    public function send(string|array $to, Mailable $mailable): bool;

    /**
     * Envia e-mail para múltiplos destinatários
     *
     * @param array $to Lista de destinatários
     * @param Mailable $mailable Instância do Mailable a ser enviado
     * @return array Resultado do envio ['sent' => int, 'failed' => int]
     * @throws \Exception Em caso de erro crítico
     */
    public function sendBulk(array $to, Mailable $mailable): array;

    /**
     * Verifica se o adaptador está configurado corretamente
     *
     * @return bool True se configurado, false caso contrário
     */
    public function isConfigured(): bool;

    /**
     * Retorna o nome do provedor de e-mail
     *
     * @return string Nome do provedor (ex: 'smtp', 'ses', 'postmark', 'mailchimp')
     */
    public function getProviderName(): string;
}






