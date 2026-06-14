<?php

namespace App\Services\Sale;

use App\Exceptions\CreditException;
use App\Exceptions\RegulatoryException;
use App\Exceptions\StockException;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Events\SaleOrderConfirmedEvent;
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
    ) {}

    public function list(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator
    {
        $params = ['status' => $status, 'per_page' => $perPage];

        return $this->cacheService->getSaleOrderList(
            $tenantId,
            $params,
            fn () => $this->saleOrderRepository->paginateForTenant($tenantId, $status, $perPage)
        );
    }

    public function find(int $tenantId, int $id, array $with = []): ?SaleOrder
    {
        return $this->saleOrderRepository->findForTenant($tenantId, $id, $with);
    }

    public function create(int $tenantId, int $userId, array $validated, array $items): SaleOrder
    {
        $status   = $validated['status'] ?? 'orcamento';
        $clientId = $validated['client_id'] ?? null;
        unset($validated['items']);

        $items = $this->priceTableService->applyPricesToItems($clientId, $items);

        if (in_array($status, ['aprovado', 'separacao', 'faturado', 'entregue']) && !empty($items)) {
            $this->saleOrderStockService->validateItemsStock($tenantId, $items, $userId);
        }

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

            if (in_array($status, ['aprovado', 'separacao', 'faturado', 'entregue'])) {
                $this->creditLimitService->validateForSaleOrder($order);
                $this->saleOrderStockService->reserveForOrder($order, $userId);
            }

            if (in_array($status, ['faturado', 'entregue'])) {
                $this->saleOrderStockService->fulfillForOrder($order, $userId);
                $this->saleOrderFinancialService->createReceivableOnBilling($order);
            }

            return $order;
        });

        $this->cacheService->invalidateSaleOrderCache($tenantId);

        // Dispara e-mail de confirmação quando o pedido é aprovado
        if (in_array($status, ['aprovado', 'separacao', 'faturado', 'entregue'])) {
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

        $updated = $this->saleOrderRepository->update($order, $data);
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

        if ($nextStatus === 'aprovado') {
            SaleOrderConfirmedEvent::dispatch($order->fresh());
        }

        return $order->fresh();
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

        if ($order->status === 'faturado') {
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
            'subtotal'         => $gross - ($gross * $discPct / 100),
            'tax_amount'       => 0,
        ]);
    }
}
