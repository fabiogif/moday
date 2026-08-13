<?php

namespace App\Services\Stock;

use App\Exceptions\StockException;
use App\Models\Batch;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class InventoryCountService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly AuditService $auditService,
    ) {}

    public function open(int $tenantId, int $warehouseId, int $userId, ?string $notes = null): InventoryCount
    {
        return InventoryCount::create([
            'tenant_id'    => $tenantId,
            'warehouse_id' => $warehouseId,
            'status'       => 'open',
            'notes'        => $notes,
            'opened_by'    => $userId,
        ]);
    }

    public function addItem(InventoryCount $count, array $data): InventoryCountItem
    {
        if ($count->status !== 'open') {
            throw StockException::invalidMovement('Inventário já foi encerrado.');
        }

        $systemQty = 0;
        if (!empty($data['batch_id'])) {
            $batch = Batch::find($data['batch_id']);
            $systemQty = (float) ($batch?->quantity_available ?? 0);
        }

        $counted = (float) $data['counted_quantity'];
        $variance = $counted - $systemQty;

        return InventoryCountItem::create([
            'inventory_count_id' => $count->id,
            'product_id'         => $data['product_id'],
            'batch_id'           => $data['batch_id'] ?? null,
            'system_quantity'    => $systemQty,
            'counted_quantity'   => $counted,
            'variance'           => $variance,
            'notes'              => $data['notes'] ?? null,
        ]);
    }

    public function close(InventoryCount $count, int $userId): InventoryCount
    {
        if ($count->status !== 'open') {
            throw StockException::invalidMovement('Inventário já foi encerrado.');
        }

        return DB::transaction(function () use ($count, $userId) {
            $count->load('items');

            foreach ($count->items as $item) {
                if ($item->variance == 0 || !$item->batch_id) {
                    continue;
                }

                $this->stockService->recordAdjustment(
                    $count->tenant_id,
                    $item->batch_id,
                    (float) $item->variance,
                    $userId,
                    "Ajuste inventário #{$count->id}"
                );
            }

            $count->update([
                'status'    => 'closed',
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);

            $this->auditService->log(
                $count->tenant_id,
                $userId,
                'inventory.closed',
                'inventory_count',
                $count->id,
                ['items' => $count->items->count()]
            );

            return $count->fresh('items');
        });
    }
}
