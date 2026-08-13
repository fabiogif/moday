<?php

namespace App\Exceptions;

use Exception;

class FiscalException extends Exception
{
    public static function orderNotBillable(string $status): self
    {
        return new self("NF-e só pode ser solicitada para pedidos faturados. Status atual: {$status}.");
    }

    public static function providerNotConfigured(): self
    {
        return new self('Provedor fiscal não configurado. Acesse Configurações → Integração Fiscal.');
    }

    public static function invalidWebhookToken(): self
    {
        return new self('Token de webhook fiscal inválido.');
    }
}
