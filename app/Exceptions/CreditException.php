<?php

namespace App\Exceptions;

use Exception;

class CreditException extends Exception
{
    public static function limitExceeded(float $limit, float $openBalance, float $orderTotal): self
    {
        return new self(
            sprintf(
                'Limite de crédito excedido. Limite: R$ %.2f, em aberto: R$ %.2f, pedido: R$ %.2f.',
                $limit,
                $openBalance,
                $orderTotal
            )
        );
    }

    public static function clientBlocked(string $reason = ''): self
    {
        $msg = 'Cliente bloqueado para novas vendas.';
        if ($reason) {
            $msg .= ' Motivo: ' . $reason;
        }
        return new self($msg);
    }
}
