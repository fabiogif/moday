<?php

namespace App\Services\Purchase;

use App\Exceptions\StockException;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly PurchaseReceiveService $purchaseReceiveService,
        private readonly CacheService $cacheService,
    ) {}

    public function list(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator
    {
        $params = ['status' => $status, 'per_page' => $perPage];

        return $this->cacheService->getPurchaseOrderList(
            $tenantId,
            $params,
            fn () => $this->purchaseOrderRepository->paginateForTenant($tenantId, $status, $perPage)
        );
    }

    public function find(int $tenantId, int $id, array $with = []): ?PurchaseOrder
    {
        return $this->purchaseOrderRepository->findForTenant($tenantId, $id, $with);
    }

    public function create(int $tenantId, array $validated, array $items): PurchaseOrder
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity_ordered'] * $item['unit_cost'];
        }

        $freight  = (float) ($validated['freight_amount'] ?? 0);
        $discount = (float) ($validated['discount_amount'] ?? 0);

        $order = $this->purchaseOrderRepository->create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'status'    => 'rascunho',
            'subtotal'  => $subtotal,
            'total'     => max(0, $subtotal + $freight - $discount),
        ]));

        foreach ($items as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'product_id'        => $item['product_id'],
                'quantity_ordered'  => $item['quantity_ordered'],
                'quantity_received' => 0,
                'unit_cost'         => $item['unit_cost'],
                'subtotal'          => $item['quantity_ordered'] * $item['unit_cost'],
            ]);
        }

        $this->cacheService->invalidatePurchaseOrderCache($tenantId);

        return $order->load(['supplier:id,company_name,trade_name', 'items.product:id,name,sku']);
    }

    public function update(int $tenantId, int $id, array $data): ?string
    {
        $order = $this->purchaseOrderRepository->findForTenant($tenantId, $id);
        if (!$order) {
            return 'not_found';
        }

        if (!$order->isEditable()) {
            return 'not_editable';
        }

        $this->purchaseOrderRepository->update($order, $data);
        $this->cacheService->invalidatePurchaseOrderCache($tenantId);

        return 'updated';
    }

    public function delete(int $tenantId, int $id): ?string
    {
        $order = $this->purchaseOrderRepository->findForTenant($tenantId, $id);
        if (!$order) {
            return 'not_found';
        }

        if (!in_array($order->status, ['rascunho', 'cancelado'])) {
            return 'not_deletable';
        }

        $this->purchaseOrderRepository->deleteWithItems($order);
        $this->cacheService->invalidatePurchaseOrderCache($tenantId);

        return 'deleted';
    }

    public function advanceStatus(int $tenantId, int $id): PurchaseOrder
    {
        $order = $this->purchaseOrderRepository->findForTenant($tenantId, $id);
        if (!$order) {
            throw new \InvalidArgumentException('Pedido não encontrado');
        }

        $flow = ['rascunho' => 'enviado', 'enviado' => 'confirmado'];

        if (!isset($flow[$order->status])) {
            throw new \DomainException('Status não pode ser avançado');
        }

        $nextStatus = $flow[$order->status];

        if ($nextStatus === 'confirmado') {
            $this->assertSupplierApproved($order);
        }

        $updated = $this->purchaseOrderRepository->update($order, ['status' => $nextStatus]);
        $this->cacheService->invalidatePurchaseOrderCache($tenantId);

        return $updated;
    }

    public function receive(PurchaseOrder $order, array $items, int $userId): PurchaseOrder
    {
        $result = $this->purchaseReceiveService->receive($order, $items, $userId);
        $this->cacheService->invalidatePurchaseOrderCache($order->tenant_id);

        return $result;
    }

    private function assertSupplierApproved(PurchaseOrder $order): void
    {
        if (!$order->supplier_id) {
            return;
        }

        $supplier = Supplier::find($order->supplier_id);
        if ($supplier && !$supplier->is_active) {
            throw StockException::invalidMovement('Fornecedor não está homologado/ativo.');
        }
    }
}
