<?php

namespace App\Jobs;

use App\Models\Batch;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireBatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = now()->toDateString();

        $expiredBatches = Batch::query()
            ->where('status', 'available')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', $today)
            ->with('product:id,name,tenant_id')
            ->get();

        if ($expiredBatches->isEmpty()) {
            return;
        }

        $expiredByTenant = $expiredBatches->groupBy('product.tenant_id');

        foreach ($expiredBatches as $batch) {
            $batch->update(['status' => 'expired']);
        }

        foreach ($expiredByTenant as $tenantId => $batches) {
            if (!$tenantId) {
                continue;
            }

            $this->notifyTenant((int) $tenantId, $batches, $today);
        }

        Log::info('ExpireBatchesJob: expired batches processed', [
            'count' => $expiredBatches->count(),
            'date' => $today,
        ]);
    }

    private function notifyTenant(int $tenantId, $batches, string $today): void
    {
        $count = $batches->count();
        $names = $batches
            ->take(3)
            ->map(function ($batch) {
                return $batch->product->name ?? null;
            })
            ->filter()
            ->implode(', ');

        $suffix = '';
        if ($count > 3) {
            $remaining = $count;
            $remaining -= 3;
            $suffix = sprintf(' e mais %d outros', $remaining);
        }

        try {
            Notification::create([
                'tenant_id' => $tenantId,
                'type' => 'batch_expired',
                'title' => sprintf('%d lote(s) expiraram hoje', $count),
                'body' => sprintf(
                    'Os seguintes lotes expiraram e foram bloqueados: %s%s.',
                    $names,
                    $suffix
                ),
                'data' => [
                    'batch_count' => $count,
                    'date' => $today,
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('ExpireBatchesJob: could not create notification', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
