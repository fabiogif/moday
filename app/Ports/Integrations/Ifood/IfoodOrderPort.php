<?php

namespace App\Ports\Integrations\Ifood;

use App\DTO\Integrations\Ifood\IfoodOrderStatusDTO;

interface IfoodOrderPort
{
    /**
     * Envia atualização de status para o iFood.
     */
    public function sendStatus(string $accessToken, IfoodOrderStatusDTO $dto): void;

    /**
     * Confirma pedido recebido.
     */
    public function confirm(string $accessToken, string $externalOrderId): void;

    /**
     * Rejeita pedido.
     */
    public function reject(string $accessToken, string $externalOrderId, ?string $reason = null): void;
}

