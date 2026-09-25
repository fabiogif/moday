<?php

namespace App\Services\Integrations\Ifood;

use App\DTO\Integrations\Ifood\IfoodOrderDTO;
use App\DTO\Integrations\Ifood\IfoodOrderItemDTO;
use App\DTO\Integrations\Ifood\IfoodOrderStatusDTO;
use App\Models\Integrations\Ifood\IfoodOrder;
use App\Ports\Integrations\Ifood\IfoodOrderPort;
use App\Repositories\Contracts\IfoodApiLogRepositoryInterface;
use App\Repositories\Contracts\IfoodOrderRepositoryInterface;
use App\Repositories\Contracts\IfoodTokenRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\OrderIdentifierService;
use Illuminate\Support\Facades\Log;
use Throwable;

class IfoodOrderService
{
    public function __construct(
        private readonly IfoodOrderRepositoryInterface $ifoodOrderRepository,
        private readonly IfoodApiLogRepositoryInterface $ifoodLogRepository,
        private readonly IfoodTokenService $tokenService,
        private readonly IfoodOrderPort $orderPort,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly OrderIdentifierService $orderIdentifierService,
    ) {
    }

    /**
     * Processa payload recebido e armazena pedido iFood.
     */
    public function ingestOrder(array $payload, int $tenantId): IfoodOrder
    {
        $dto = IfoodOrderDTO::fromPayload($payload);

        $ifoodOrder = $this->ifoodOrderRepository->createOrUpdateFromDto($dto, $tenantId);

        $items = array_map(
            fn (IfoodOrderItemDTO $item) => $item->toArray(),
            $dto->items
        );

        if (!empty($items)) {
            $this->ifoodOrderRepository->attachItems($ifoodOrder, $items);
        }

        $this->ifoodLogRepository->create([
            'tenant_id' => $tenantId,
            'level' => 'info',
            'context' => 'ingest_order',
            'action' => 'order_received',
            'payload' => $payload,
            'message' => sprintf('Pedido iFood %s recebido e armazenado.', $dto->externalOrderId),
        ]);

        $this->ensureInternalOrder($ifoodOrder, $dto, $tenantId);

        return $ifoodOrder->fresh(['items', 'order']);
    }

    /**
     * Envia atualização de status ao iFood e registra log.
     */
    public function sendStatusUpdate(IfoodOrder $ifoodOrder, string $status, int $tenantId, ?string $reason = null, ?string $description = null): void
    {
        $token = $this->tokenService->getValidAccessToken($tenantId);

        $dto = new IfoodOrderStatusDTO(
            externalOrderId: $ifoodOrder->external_order_id,
            status: $status,
            description: $description,
            reason: $reason,
        );

        try {
            $this->orderPort->sendStatus($token, $dto);

            $this->ifoodOrderRepository->attachStatus($ifoodOrder, [
                'status' => $status,
                'sent_to_ifood_at' => now(),
                'response_code' => 200,
            ]);

            $ifoodOrder->update([
                'status' => $status,
                'last_synced_at' => now(),
            ]);

            $this->ifoodLogRepository->create([
                'tenant_id' => $tenantId,
                'level' => 'info',
                'context' => 'status_update',
                'action' => 'status_sent',
                'payload' => $dto->toArray(),
                'message' => sprintf('Status "%s" enviado ao iFood para pedido %s.', $status, $ifoodOrder->external_order_id),
            ]);
        } catch (Throwable $exception) {
            Log::channel('ifood')->error('Falha ao enviar status ao iFood', [
                'external_order_id' => $ifoodOrder->external_order_id,
                'status' => $status,
                'exception' => $exception->getMessage(),
            ]);

            $this->ifoodOrderRepository->attachStatus($ifoodOrder, [
                'status' => $status,
                'error_message' => $exception->getMessage(),
            ]);

            $this->ifoodLogRepository->create([
                'tenant_id' => $tenantId,
                'level' => 'error',
                'context' => 'status_update',
                'action' => 'status_error',
                'payload' => $dto->toArray(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Confirma pedido no iFood.
     */
    public function confirm(IfoodOrder $ifoodOrder, int $tenantId): void
    {
        $token = $this->tokenService->getValidAccessToken($tenantId);

        $this->orderPort->confirm($token, $ifoodOrder->external_order_id);

        $this->ifoodOrderRepository->attachStatus($ifoodOrder, [
            'status' => 'CONFIRMED',
            'sent_to_ifood_at' => now(),
            'response_code' => 200,
        ]);

        $ifoodOrder->update([
            'status' => 'Confirmado',
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Garante criação de pedido interno associado.
     */
    private function ensureInternalOrder(IfoodOrder $ifoodOrder, IfoodOrderDTO $dto, int $tenantId): void
    {
        if ($ifoodOrder->order_id) {
            return;
        }

        $products = $this->mapProducts($dto->items, $tenantId);

        $identify = $this->orderIdentifierService->generate();

        $delivery = $dto->getDeliveryAddress();

        $deliveryData = [
            'is_delivery' => true,
            'delivery_address' => $delivery['street'] ?? null,
            'delivery_number' => $delivery['number'] ?? null,
            'delivery_complement' => $delivery['complement'] ?? null,
            'delivery_neighborhood' => $delivery['district'] ?? null,
            'delivery_city' => $delivery['city'] ?? null,
            'delivery_state' => $delivery['state'] ?? null,
            'delivery_zip_code' => $delivery['postalCode'] ?? null,
            'delivery_notes' => $dto->rawPayload['delivery']['instructions'] ?? null,
        ];

        $order = $this->orderRepository->createNewOrder(
            identify: $identify,
            total: $dto->totalAmount,
            status: 'Recebido do iFood',
            tenantId: $tenantId,
            comment: 'Pedido importado automaticamente do iFood',
            clientId: null,
            tableId: null,
            deliveryData: $deliveryData,
            paymentMethodId: null,
            orderStatusId: null
        );

        if (!empty($products)) {
            $this->orderRepository->registerProductsOrder($order->id, $products);
        }

        $this->ifoodOrderRepository->linkInternalOrder($ifoodOrder, $order->id);
    }

    /**
     * Mapeia itens do iFood para produtos internos (quando possível).
     *
     * @param array<IfoodOrderItemDTO> $items
     */
    private function mapProducts(array $items, int $tenantId): array
    {
        $mapped = [];

        foreach ($items as $item) {
            if (!$item instanceof IfoodOrderItemDTO) {
                continue;
            }

            $sku = $item->sku ?? $item->externalCode;

            if (!$sku) {
                continue;
            }

            $product = $this->productRepository->findBySku($sku, $tenantId);

            if (!$product) {
                continue;
            }

            $quantity = $item->quantity > 0 ? $item->quantity : 1;
            $price = $item->unitPrice ?? $item->price ?? 0;

            $mapped[] = [
                'id' => $product->id,
                'qty' => max(1, (int) round($quantity)),
                'price' => $price,
            ];
        }

        return $mapped;
    }

    public function listByTenant(int $tenantId, int $perPage = 20)
    {
        return $this->ifoodOrderRepository->paginateByTenant($tenantId, $perPage);
    }

    public function findForTenant(string $externalOrderId, int $tenantId): ?IfoodOrder
    {
        $ifoodOrder = $this->ifoodOrderRepository->findByExternalId($externalOrderId);

        if (!$ifoodOrder || $ifoodOrder->tenant_id !== $tenantId) {
            return null;
        }

        return $ifoodOrder;
    }

    public function getOrderDetails(string $externalOrderId, int $tenantId): ?IfoodOrder
    {
        $ifoodOrder = $this->findForTenant($externalOrderId, $tenantId);

        return $ifoodOrder?->load(['items', 'statusLogs', 'order']);
    }
}

