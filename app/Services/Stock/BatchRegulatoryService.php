<?php

namespace App\Services\Stock;

use App\Exceptions\StockException;
use App\Models\Batch;
use App\Models\StockReservation;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class BatchRegulatoryService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly StockService $stockService,
    ) {}

    public function setStatus(Batch $batch, string $status, int $userId, ?string $reason = null): Batch
    {
        if (!in_array($status, ['available', 'quarantine', 'recalled', 'expired'], true)) {
            throw StockException::invalidMovement('Status de lote inválido.');
        }

        return DB::transaction(function () use ($batch, $status, $userId, $reason) {
            if (in_array($status, ['quarantine', 'recalled'])) {
                $this->blockActiveReservations($batch);
            }

            $batch->update([
                'status' => $status,
                'notes'  => trim(($batch->notes ?? '') . "\n[{$status}] " . ($reason ?? now()->toDateTimeString())),
            ]);

            $this->auditService->log(
                $batch->tenant_id,
                $userId,
                "batch.{$status}",
                'batch',
                $batch->id,
                ['reason' => $reason]
            );

            $this->stockService->syncProductStock($batch->product_id);

            return $batch->fresh();
        });
    }

    private function blockActiveReservations(Batch $batch): void
    {
        $reservations = StockReservation::where('batch_id', $batch->id)
            ->where('status', 'reserved')
            ->get();

        foreach ($reservations as $reservation) {
            $batchLocked = Batch::lockForUpdate()->find($batch->id);
            $batchLocked->quantity_reserved = max(
                0,
                (float) $batchLocked->quantity_reserved - (float) $reservation->quantity
            );
            $batchLocked->save();
            $reservation->update(['status' => 'released']);
        }
    }
}
