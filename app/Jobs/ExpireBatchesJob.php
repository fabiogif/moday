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

        // Find batches that have expired but are still marked as available
        $expiredBatches = Batch::where('status', 'available')
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

        // Notify each tenant about expired batches
        foreach ($expiredByTenant as $tenantId => $batches) {
            if (!$tenantId) continue;

            $count = $batches->count();
            $names = $batches->take(3)->map(fn ($b) => $b->product?->name)->filter()->implode(', ');
            $suffix = $count > 3 ? " e mais {$count - 3} outros" : '';

            try {
                Notification::create([
                    'tenant_id' => $tenantId,
                    'type'      => 'batch_expired',
                    'title'     => "{$count} lote(s) expiraram hoje",
                    'body'      => "Os seguintes lotes expiraram e foram bloqueados: {$names}{$suffix}.",
                    'data'      => ['batch_count' => $count, 'date' => $today],
                ]);
            } catch (\Exception $e) {
                Log::warning('ExpireBatchesJob: could not create notification', [
                    'tenant_id' => $tenantId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        Log::info('ExpireBatchesJob: expired batches processed', [
            'count' => $expiredBatches->count(),
            'date'  => $today,
        ]);
    }
}
