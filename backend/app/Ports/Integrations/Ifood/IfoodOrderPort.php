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

    /**
     * Despacha pedido de entrega (DELIVERY).
     */
    public function dispatchOrder(string $accessToken, string $externalOrderId): void;

    /**
     * Marca pedido de retirada (TAKEOUT) como pronto para retirada.
     */
    public function readyToPickup(string $accessToken, string $externalOrderId): void;

    /**
     * Busca eventos pendentes via polling.
     */
    public function pollEvents(string $accessToken): array;

    /**
     * Confirma recebimento de lote de eventos.
     */
    public function acknowledgeEvents(string $accessToken, array $eventIds): void;

    /**
     * Obtém detalhes completos do pedido.
     */
    public function getOrderDetails(string $accessToken, string $externalOrderId): array;
}

