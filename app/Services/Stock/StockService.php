<?php

namespace App\Services\Stock;

use App\Exceptions\StockException;
use App\Models\Batch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(
        private readonly FefoAllocationService $fefoAllocationService,
    ) {}

    public function recordEntry(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        int $performedBy,
        array $options = [],
    ): Batch {
        return DB::transaction(function () use ($tenantId, $productId, $warehouseId, $quantity, $performedBy, $options) {
            if ($quantity <= 0) {
                throw StockException::invalidMovement('Quantidade de entrada deve ser maior que zero.');
            }

            $batchNumber = $options['batch_number'] ?? 'ENT-' . now()->format('YmdHis');
            $unitCost    = $options['unit_cost'] ?? null;

            $batch = Batch::create([
                'tenant_id'          => $tenantId,
                'product_id'         => $productId,
                'warehouse_id'       => $warehouseId,
                'supplier_id'        => $options['supplier_id'] ?? null,
                'purchase_order_id'  => $options['purchase_order_id'] ?? null,
                'batch_number'       => $batchNumber,
                'manufacture_date'   => $options['manufacture_date'] ?? null,
                'expiry_date'        => $options['expiry_date'] ?? null,
                'quantity_received'  => $quantity,
                'quantity_available' => $quantity,
                'quantity_reserved'  => 0,
                'quantity_sold'      => 0,
                'unit_cost'          => $unitCost,
                'status'             => $this->resolveBatchStatus($options['expiry_date'] ?? null),
                'notes'              => $options['notes'] ?? null,
            ]);

            $this->createMovement(
                tenantId: $tenantId,
                productId: $productId,
                batchId: $batch->id,
                warehouseId: $warehouseId,
                performedBy: $performedBy,
                type: 'entrada',
                quantity: $quantity,
                unitCost: $unitCost,
                referenceType: $options['reference_type'] ?? null,
                referenceId: $options['reference_id'] ?? null,
                notes: $options['notes'] ?? null,
            );

            $this->syncProductStock($productId);

            return $batch->fresh();
        });
    }

    public function recordAdjustment(
        int $tenantId,
        int $batchId,
        float $quantityDelta,
        int $performedBy,
        ?string $notes = null,
    ): Batch {
        return DB::transaction(function () use ($tenantId, $batchId, $quantityDelta, $performedBy, $notes) {
            if ($quantityDelta == 0) {
                throw StockException::invalidMovement('Ajuste com quantidade zero não é permitido.');
            }

            $batch = Batch::forTenant($tenantId)->lockForUpdate()->findOrFail($batchId);

            if ($quantityDelta < 0) {
                $abs = abs($quantityDelta);
                $free = (float) $batch->quantity_available - (float) $batch->quantity_reserved;
                if ($abs > $free) {
                    throw StockException::insufficientStock(
                        $batch->product?->name ?? 'produto',
                        $abs,
                        $free
                    );
                }
            }

            $batch->quantity_available = max(0, (float) $batch->quantity_available + $quantityDelta);
            if ($quantityDelta > 0) {
                $batch->quantity_received = (float) $batch->quantity_received + $quantityDelta;
            }
            $batch->save();

            $this->createMovement(
                tenantId: $tenantId,
                productId: $batch->product_id,
                batchId: $batch->id,
                warehouseId: $batch->warehouse_id,
                performedBy: $performedBy,
                type: 'ajuste',
                quantity: $quantityDelta,
                unitCost: $batch->unit_cost,
                notes: $notes,
            );

            $this->syncProductStock($batch->product_id);

            return $batch->fresh();
        });
    }

    public function recordTransfer(
        int $tenantId,
        int $batchId,
        int $toWarehouseId,
        float $quantity,
        int $performedBy,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($tenantId, $batchId, $toWarehouseId, $quantity, $performedBy, $notes) {
            if ($quantity <= 0) {
                throw StockException::invalidMovement('Quantidade de transferência deve ser maior que zero.');
            }

            $source = Batch::forTenant($tenantId)->lockForUpdate()->findOrFail($batchId);
            $free   = (float) $source->quantity_available - (float) $source->quantity_reserved;

            if ($quantity > $free) {
                throw StockException::insufficientStock(
                    $source->product?->name ?? 'produto',
                    $quantity,
                    $free
                );
            }

            $source->quantity_available = $free + (float) $source->quantity_reserved - $quantity;
            $source->save();

            $this->createMovement(
                tenantId: $tenantId,
                productId: $source->product_id,
                batchId: $source->id,
                warehouseId: $source->warehouse_id,
                performedBy: $performedBy,
                type: 'transferencia',
                quantity: -$quantity,
                unitCost: $source->unit_cost,
                notes: $notes ?? "Transferência saída para armazém #{$toWarehouseId}",
            );

            $destBatch = Batch::create([
                'tenant_id'          => $tenantId,
                'product_id'         => $source->product_id,
                'warehouse_id'       => $toWarehouseId,
                'supplier_id'        => $source->supplier_id,
                'purchase_order_id'  => $source->purchase_order_id,
                'batch_number'       => $source->batch_number,
                'manufacture_date'   => $source->manufacture_date,
                'expiry_date'        => $source->expiry_date,
                'quantity_received'  => $quantity,
                'quantity_available' => $quantity,
                'quantity_reserved'  => 0,
                'quantity_sold'      => 0,
                'unit_cost'          => $source->unit_cost,
                'status'             => $source->status,
                'notes'              => $notes,
            ]);

            $this->createMovement(
                tenantId: $tenantId,
                productId: $source->product_id,
                batchId: $destBatch->id,
                warehouseId: $toWarehouseId,
                performedBy: $performedBy,
                type: 'transferencia',
                quantity: $quantity,
                unitCost: $source->unit_cost,
                notes: $notes ?? "Transferência entrada do armazém #{$source->warehouse_id}",
            );

            $this->syncProductStock($source->product_id);

            return ['source' => $source->fresh(), 'destination' => $destBatch];
        });
    }

    public function recordExit(
        int $tenantId,
        int $batchId,
        float $quantity,
        int $performedBy,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): Batch {
        return DB::transaction(function () use ($tenantId, $batchId, $quantity, $performedBy, $referenceType, $referenceId, $notes) {
            if ($quantity <= 0) {
                throw StockException::invalidMovement('Quantidade de saída deve ser maior que zero.');
            }

            $batch = Batch::forTenant($tenantId)->lockForUpdate()->findOrFail($batchId);
            $this->assertBatchAvailable($batch);

            $free = (float) $batch->quantity_available - (float) $batch->quantity_reserved;
            if ($quantity > $free) {
                throw StockException::insufficientStock(
                    $batch->product?->name ?? 'produto',
                    $quantity,
                    $free
                );
            }

            $batch->quantity_available = (float) $batch->quantity_available - $quantity;
            $batch->quantity_sold      = (float) $batch->quantity_sold + $quantity;
            $batch->save();

            $this->createMovement(
                tenantId: $tenantId,
                productId: $batch->product_id,
                batchId: $batch->id,
                warehouseId: $batch->warehouse_id,
                performedBy: $performedBy,
                type: 'saida',
                quantity: $quantity,
                unitCost: $batch->unit_cost,
                referenceType: $referenceType,
                referenceId: $referenceId,
                notes: $notes,
            );

            $this->syncProductStock($batch->product_id);

            return $batch->fresh();
        });
    }

    public function recordReturn(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        int $performedBy,
        array $options = [],
    ): Batch {
        return $this->recordEntry($tenantId, $productId, $warehouseId, $quantity, $performedBy, array_merge($options, [
            'batch_number'   => $options['batch_number'] ?? 'DEV-' . now()->format('YmdHis'),
            'reference_type' => $options['reference_type'] ?? 'return',
            'notes'          => $options['notes'] ?? 'Devolução de mercadoria',
        ]));
    }

    public function syncProductStock(int $productId): void
    {
        $total = Batch::where('product_id', $productId)
            ->where('status', 'available')
            ->sum('quantity_available');

        Product::where('id', $productId)->update(['qtd_stock' => $total]);
    }

    public function getAvailableQuantity(int $tenantId, int $productId, ?int $warehouseId = null): float
    {
        return $this->fefoAllocationService->getAvailableQuantity($tenantId, $productId, $warehouseId);
    }

    /**
     * Garante que o estoque declarado no cadastro (qtd_stock) exista em lotes disponíveis.
     * Corrige produtos legados criados com qtd_stock sem movimentação de entrada.
     */
    public function ensureStockFromDeclaredQuantity(int $tenantId, int $productId, int $performedBy): void
    {
        $product = Product::find($productId);
        if (!$product || (float) $product->qtd_stock <= 0) {
            return;
        }

        $hasBatches = Batch::forTenant($tenantId)->where('product_id', $productId)->exists();
        if ($hasBatches) {
            return;
        }

        $available = $this->fefoAllocationService->getAvailableQuantity($tenantId, $productId);
        $declared  = (float) $product->qtd_stock;
        $delta     = $declared - $available;

        if ($delta <= 0.001) {
            return;
        }

        $warehouse = Warehouse::forTenant($tenantId)
            ->active()
            ->orderByDesc('is_default')
            ->first();

        if (!$warehouse) {
            $warehouse = Warehouse::create([
                'tenant_id'   => $tenantId,
                'name'        => 'Depósito Principal',
                'is_default'  => true,
                'is_active'   => true,
            ]);
        }

        $this->recordEntry(
            $tenantId,
            $productId,
            $warehouse->id,
            $delta,
            $performedBy,
            [
                'batch_number'   => 'CAD-' . $productId . '-' . now()->format('YmdHis'),
                'unit_cost'      => $product->price_cost,
                'notes'          => 'Entrada automática — sincronização com estoque declarado no cadastro',
                'reference_type' => 'product_stock_sync',
                'reference_id'   => $productId,
            ],
        );
    }

    private function assertBatchAvailable(Batch $batch): void
    {
        if (in_array($batch->status, ['quarantine', 'recalled'], true)) {
            throw StockException::batchBlocked($batch->batch_number, "status {$batch->status}");
        }

        if ($batch->isExpired()) {
            $batch->update(['status' => 'expired']);
            throw StockException::batchBlocked($batch->batch_number, 'lote vencido');
        }
    }

    private function resolveBatchStatus(?string $expiryDate): string
    {
        if (!$expiryDate) {
            return 'available';
        }

        return Carbon::parse($expiryDate)->isPast() ? 'expired' : 'available';
    }

    private function createMovement(
        int $tenantId,
        int $productId,
        ?int $batchId,
        ?int $warehouseId,
        int $performedBy,
        string $type,
        float $quantity,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): StockMovement {
        return StockMovement::create([
            'tenant_id'      => $tenantId,
            'product_id'     => $productId,
            'batch_id'       => $batchId,
            'warehouse_id'   => $warehouseId,
            'performed_by'   => $performedBy,
            'type'           => $type,
            'quantity'       => $quantity,
            'unit_cost'      => $unitCost,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'notes'          => $notes,
        ]);
    }
}
