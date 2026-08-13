<?php

namespace App\Adapters\Email;

use App\Adapters\Email\Contracts\EmailAdapterInterface;
use Illuminate\Support\Facades\Log;

/**
 * Factory para criar adaptadores de e-mail
 * 
 * Responsável por instanciar o adaptador correto baseado na configuração
 */
class EmailAdapterFactory
{
    /**
     * Cria o adaptador de e-mail baseado na configuração
     *
     * @param string|null $provider Nome do provedor (opcional, usa config se não fornecido)
     * @return EmailAdapterInterface Instância do adaptador
     * @throws \InvalidArgumentException Se o provedor não for suportado
     */
    public static function create(?string $provider = null): EmailAdapterInterface
    {
        $provider = $provider ?? config('mail.provider', 'smtp');

        return match (strtolower($provider)) {
            'smtp' => new SmtpAdapter(),
            'ses', 'aws-ses', 'aws_ses' => new SesAdapter(),
            'postmark' => new PostmarkAdapter(),
            'mailchimp', 'mandrill' => new MailchimpAdapter(),
            default => throw new \InvalidArgumentException(
                "Provedor de e-mail '{$provider}' não é suportado. " .
                "Provedores suportados: smtp, ses, postmark, mailchimp"
            ),
        };
    }

    /**
     * Retorna o adaptador padrão configurado
     *
     * @return EmailAdapterInterface
     */
    public static function default(): EmailAdapterInterface
    {
        return self::create();
    }

    /**
     * Verifica se um provedor está disponível e configurado
     *
     * @param string $provider Nome do provedor
     * @return bool True se disponível e configurado
     */
    public static function isAvailable(string $provider): bool
    {
        try {
            $adapter = self::create($provider);
            return $adapter->isConfigured();
        } catch (\Exception $e) {
            Log::warning('EmailAdapterFactory: Provedor não disponível', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Lista todos os provedores disponíveis
     *
     * @return array Lista de nomes de provedores
     */
    public static function getAvailableProviders(): array
    {
        return ['smtp', 'ses', 'postmark', 'mailchimp'];
    }
}






