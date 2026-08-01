<?php

namespace App\Services\Sale;

use App\Exceptions\CreditException;
use App\Exceptions\RegulatoryException;
use App\Exceptions\StockException;
use App\Models\Client;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Events\SaleOrderConfirmedEvent;
use App\Events\SaleOrderCreated;
use App\Events\SaleOrderStatusChangedEvent;
use Illuminate\Support\Facades\Log;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;
use App\Services\CacheService;
use App\Services\Commercial\CreditLimitService;
use App\Services\Commercial\PriceTableService;
use App\Services\Logistics\PickingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SaleOrderService
{
    public function __construct(
        private readonly SaleOrderRepositoryInterface $saleOrderRepository,
        private readonly SaleOrderStockService $saleOrderStockService,
        private readonly PriceTableService $priceTableService,
        private readonly CreditLimitService $creditLimitService,
        private readonly SaleOrderFinancialService $saleOrderFinancialService,
        private readonly SaleReturnService $saleReturnService,
        private readonly PickingService $pickingService,
        private readonly CacheService $cacheService,
        private readonly OfferEngineService $offerEngineService,
    ) {}

    public function list(int $tenantId, ?string $status, int $perPage, ?string $search = null, int $page = 1): LengthAwarePaginator
    {
        // Inclui a página na chave de cache; sem isso, a página 2+ retorna
        // o mesmo resultado cacheado da página 1, travando a paginação.
        $params = ['status' => $status, 'per_page' => $perPage, 'search' => $search, 'page' => $page];

        return $this->cacheService->getSaleOrderList(
            $tenantId,
            $params,
            fn () => $this->saleOrderRepository->paginateForTenant($tenantId, $status, $perPage, $search, $page)
        );
    }

    /**
     * Pendentes (orçamentos em aberto) + série diária de pedidos no período,
     * usado pelo gráfico da Home do app mobile. "todos" não tem um recorte de
     * dias natural, então vem sem série (só total/pendentes).
     */
    public function summary(int $tenantId, string $period): array
    {
        $start = match ($period) {
            'hoje' => now()->startOfDay(),
            '7d'   => now()->startOfDay()->subDays(6),
            '30d'  => now()->startOfDay()->subDays(29),
            default => null,
        };
        $end = now();

        return $this->cacheService->getSaleOrderSummary(
            $tenantId,
            ['period' => $period],
            fn () => $this->saleOrderRepository->summaryForTenant($tenantId, $start, $end)
        );
    }

    public function find(int $tenantId, int $id, array $with = []): ?SaleOrder
    {
        return $this->saleOrderRepository->findForTenant($tenantId, $id, $with);
    }

    public function create(int $tenantId, int $userId, array $validated, array $items): SaleOrder
    {
        $offlineId = $validated['offline_id'] ?? null;

        if ($offlineId !== null) {
            $existing = $this->saleOrderRepository->findByOfflineId($tenantId, $offlineId);
            if ($existing) {
                return $existing;
            }
        }

        $status   = $validated['status'] ?? 'orcamento';
        $clientId = $validated['client_id'] ?? null;
        unset($validated['items']);

        $shippingData = $this->resolveShippingAddress($tenantId, $validated);
        $validated    = array_merge($validated, $shippingData);

        $items = $this->priceTableService->applyPricesToItems($clientId, $items);
        $items = $this->offerEngineService->evaluate($tenantId, $items)['items'];

        if (in_array($status, ['aprovado', 'separacao', 'faturado', 'em_transito', 'entregue']) && !empty($items)) {
            $this->saleOrderStockService->validateItemsStock($tenantId, $items, $userId);
        }

        try {
            $order = DB::transaction(function () use ($validated, $items, $tenantId, $status, $userId) {
                $subtotal = $this->calculateSubtotal($items);
                $freight  = (float) ($validated['freight_amount'] ?? 0);
                $discount = (float) ($validated['discount_amount'] ?? 0);

                $order = $this->saleOrderRepository->create(array_merge($validated, [
                    'tenant_id' => $tenantId,
                    'status'    => $status,
                    'subtotal'  => $subtotal,
                    'total'     => max(0, $subtotal + $freight - $discount),
                ]));

                foreach ($items as $item) {
                    $this->createOrderItem($order->id, $item);
                }

                $order->load('items.product');

                if (in_array($status, ['aprovado', 'separacao', 'faturado', 'em_transito', 'entregue'])) {
                    $this->creditLimitService->validateForSaleOrder($order);
                    $this->saleOrderStockService->reserveForOrder($order, $userId);
                }

                if (in_array($status, ['faturado', 'em_transito', 'entregue'])) {
                    $this->saleOrderStockService->fulfillForOrder($order, $userId);
                    $this->saleOrderFinancialService->createReceivableOnBilling($order);
                }

                return $order;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ($offlineId !== null && $this->isUniqueConstraintViolation($e)) {
                $existing = $this->saleOrderRepository->findByOfflineId($tenantId, $offlineId);
                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }

        $this->cacheService->invalidateSaleOrderCache($tenantId);

        try {
            SaleOrderCreated::dispatch($order->loadMissing('client'));
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast SaleOrderCreated event: ' . $e->getMessage());
        }

        // Dispara e-mail de confirmação quando o pedido é aprovado
        if (in_array($status, ['aprovado', 'separacao', 'faturado', 'em_transito', 'entregue'])) {
            SaleOrderConfirmedEvent::dispatch($order);
        }

        return $order;
    }

    public function update(int $tenantId, int $id, array $data): ?SaleOrder
    {
        $order = $this->saleOrderRepository->findForTenant($tenantId, $id);
        if (!$order) {
            return null;
        }

        $items = $data['items'] ?? null;
        unset($data['items']);

        if ($items !== null && $order->status !== 'orcamento') {
            throw new \DomainException('Itens só podem ser editados em pedidos em orçamento.');
        }

        if ($this->hasShippingInput($data)) {
            $shippingData = $this->resolveShippingAddress($tenantId, array_merge(
                $order->only(['client_id', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zipcode', 'use_client_address']),
                $data
            ));
            $data = array_merge($data, $shippingData);
        }

        $updated = DB::transaction(function () use ($order, $data, $items, $tenantId) {
            if ($items !== null) {
                $clientId = $data['client_id'] ?? $order->client_id;
                $items    = $this->priceTableService->applyPricesToItems($clientId, $items);
                $items    = $this->offerEngineService->evaluate($tenantId, $items)['items'];

                $order->items()->delete();
                foreach ($items as $item) {
                    $this->createOrderItem($order->id, $item);
                }

                $subtotal = $this->calculateSubtotal($items);
                $freight  = (float) ($data['freight_amount'] ?? $order->freight_amount ?? 0);
                $discount = (float) ($data['discount_amount'] ?? $order->discount_amount ?? 0);

                $data['subtotal'] = $subtotal;
                $data['total']    = max(0, $subtotal + $freight - $discount);
            }

            return $this->saleOrderRepository->update($order, $data);
        });

        $this->cacheService->invalidateSaleOrderCache($tenantId);

        return $updated;
    }

    public function delete(int $tenantId, int $id): ?string
    {
        $order = $this->saleOrderRepository->findForTenant($tenantId, $id);
        if (!$order) {
            return 'not_found';
        }

        if (!in_array($order->status, ['orcamento', 'cancelado'])) {
            return 'not_deletable';
        }

        $this->saleOrderRepository->deleteWithItems($order);
        $this->cacheService->invalidateSaleOrderCache($tenantId);

        return 'deleted';
    }

    public function advanceStatus(int $tenantId, int $id, int $userId): SaleOrder
    {
        $order = $this->saleOrderRepository->findForTenant($tenantId, $id, ['items.product']);
        if (!$order) {
            throw new \InvalidArgumentException('Pedido não encontrado');
        }

        if (!$order->canAdvance()) {
            throw new \DomainException('Pedido não pode ter status avançado');
        }

        $nextStatus = $order->nextStatus();

        DB::transaction(function () use ($order, $userId, $nextStatus) {
            if ($nextStatus === 'aprovado') {
                $this->saleOrderStockService->validateItemsStock(
                    $order->tenant_id,
                    $order->items->map(fn ($i) => [
                        'product_id' => $i->product_id,
                        'quantity'   => $i->quantity,
                    ])->all(),
                    $userId
                );
                $this->creditLimitService->validateForSaleOrder($order);
            }

            $order->advanceStatus($userId);

            if ($nextStatus === 'aprovado') {
                $this->saleOrderStockService->reserveForOrder($order->fresh(['items.product']), $userId);
            }

            if ($nextStatus === 'faturado') {
                $fresh = $order->fresh(['items']);
                if (!$this->pickingService->isFullyPicked($fresh)) {
                    throw StockException::invalidMovement('Separação incompleta. Confirme o picking antes de faturar.');
                }
                $this->saleOrderStockService->fulfillForOrder($fresh, $userId);
                $this->saleOrderFinancialService->createReceivableOnBilling($fresh);
            }
        });

        $this->cacheService->invalidateSaleOrderCache($tenantId);

        $freshOrder = $order->fresh();

        if ($nextStatus === 'aprovado') {
            SaleOrderConfirmedEvent::dispatch($freshOrder);
        }

        SaleOrderStatusChangedEvent::dispatch($freshOrder, $order->getOriginal('status') ?? 'rascunho', $nextStatus);

        return $freshOrder;
    }

    public function cancel(int $tenantId, int $id): ?string
    {
        $order = $this->saleOrderRepository->findForTenant($tenantId, $id);
        if (!$order) {
            return 'not_found';
        }

        if (in_array($order->status, ['entregue', 'cancelado'])) {
            return 'not_cancellable';
        }

        if (in_array($order->status, ['faturado', 'em_transito'])) {
            return 'billed';
        }

        DB::transaction(function () use ($order) {
            $this->saleOrderStockService->releaseForOrder($order);
            $order->update(['status' => 'cancelado']);
        });

        $this->cacheService->invalidateSaleOrderCache($tenantId);

        return 'cancelled';
    }

    public function returnItems(SaleOrder $order, array $items, int $userId): SaleOrder
    {
        $result = $this->saleReturnService->processReturn($order, $items, $userId);
        $this->cacheService->invalidateSaleOrderCache($order->tenant_id);

        return $result;
    }

    private function isUniqueConstraintViolation(\Illuminate\Database\QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }

    private function calculateSubtotal(array $items): float
    {
        $subtotal = 0;
        foreach ($items as $item) {
            if (($item['item_type'] ?? 'venda') === 'bonificacao') {
                continue;
            }
            $gross = $item['quantity'] * $item['unit_price'];
            $disc  = $gross * (($item['discount_percent'] ?? 0) / 100);
            $subtotal += $gross - $disc;
        }

        return $subtotal;
    }

    private function createOrderItem(int $orderId, array $item): void
    {
        $itemType  = $item['item_type'] ?? 'venda';
        $unitPrice = $itemType === 'bonificacao' ? 0 : (float) $item['unit_price'];
        $gross     = $item['quantity'] * $unitPrice;
        $discPct   = (float) ($item['discount_percent'] ?? 0);

        SaleOrderItem::create([
            'sale_order_id'    => $orderId,
            'product_id'       => $item['product_id'],
            'batch_id'         => $item['batch_id'] ?? null,
            'item_type'        => $itemType,
            'quantity'         => $item['quantity'],
            'unit_price'       => $unitPrice,
            'discount_percent' => $discPct,
            'offer_rule_id'    => $item['offer_rule_id'] ?? null,
            'subtotal'         => $gross - ($gross * $discPct / 100),
            'tax_amount'       => 0,
        ]);
    }

    private function hasShippingInput(array $data): bool
    {
        return array_key_exists('use_client_address', $data)
            || array_key_exists('shipping_address', $data)
            || array_key_exists('shipping_city', $data)
            || array_key_exists('shipping_state', $data)
            || array_key_exists('shipping_zipcode', $data);
    }

    /**
     * @return array{
     *     use_client_address: bool,
     *     shipping_address: ?array,
     *     shipping_city: ?string,
     *     shipping_state: ?string,
     *     shipping_zipcode: ?string
     * }
     */
    private function resolveShippingAddress(int $tenantId, array $data): array
    {
        $useClientAddress = (bool) ($data['use_client_address'] ?? false);

        if ($useClientAddress) {
            $clientId = $data['client_id'] ?? null;
            if (!$clientId) {
                throw new \DomainException('Informe o cliente para usar o endereço cadastrado.');
            }

            $client = Client::query()
                ->where('tenant_id', $tenantId)
                ->find($clientId);

            if (!$client || !$client->hasCompleteAddress()) {
                throw new \DomainException('Cliente sem endereço completo cadastrado.');
            }

            return [
                'use_client_address' => true,
                'shipping_address'   => [
                    'street'       => $client->address,
                    'number'       => $client->number,
                    'neighborhood' => $client->neighborhood,
                    'complement'   => $client->complement,
                ],
                'shipping_city'    => $client->city,
                'shipping_state'   => $client->state,
                'shipping_zipcode' => $client->zip_code,
            ];
        }

        $shippingAddress = $this->normalizeShippingAddress($data['shipping_address'] ?? null);
        $hasAnyShipping  = $shippingAddress
            || !empty($data['shipping_city'])
            || !empty($data['shipping_state'])
            || !empty($data['shipping_zipcode']);

        if (!$hasAnyShipping) {
            return [
                'use_client_address' => false,
                'shipping_address'   => null,
                'shipping_city'      => null,
                'shipping_state'     => null,
                'shipping_zipcode'   => null,
            ];
        }

        if (empty($shippingAddress['street'] ?? null) || empty($data['shipping_city']) || empty($data['shipping_state'])) {
            throw new \DomainException('Informe logradouro, cidade e estado para o endereço de entrega.');
        }

        return [
            'use_client_address' => false,
            'shipping_address'   => $shippingAddress,
            'shipping_city'      => $data['shipping_city'] ?? null,
            'shipping_state'     => $data['shipping_state'] ?? null,
            'shipping_zipcode'   => $data['shipping_zipcode'] ?? null,
        ];
    }

    private function normalizeShippingAddress(mixed $address): ?array
    {
        if (!is_array($address)) {
            return null;
        }

        return array_filter([
            'street'       => $address['street'] ?? $address['logradouro'] ?? null,
            'number'       => $address['number'] ?? $address['numero'] ?? null,
            'neighborhood' => $address['neighborhood'] ?? $address['bairro'] ?? null,
            'complement'   => $address['complement'] ?? $address['complemento'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
