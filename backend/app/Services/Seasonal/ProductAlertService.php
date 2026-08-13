<?php

namespace App\Services\Seasonal;

use App\Models\Batch;
use App\Models\SeasonalAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProductAlertService
{
    private const EXPIRY_WINDOWS = [
        ['days' => 7,  'priority' => 'critical', 'label' => '7 dias'],
        ['days' => 15, 'priority' => 'high',     'label' => '15 dias'],
        ['days' => 30, 'priority' => 'normal',   'label' => '30 dias'],
    ];

    /**
     * Scans all batches and generates SeasonalAlerts for:
     * - Expiring lots (within 7/15/30 days)
     * - Expired lots with remaining stock
     * - Promotion suggestions (expiring + slow-moving)
     *
     * Returns count of new alerts created.
     */
    public function generateBatchAlerts(int $tenantId): int
    {
        $created = 0;
        $today   = Carbon::today();

        // Expiring lots — split by window
        foreach (self::EXPIRY_WINDOWS as $window) {
            $batches = Batch::forTenant($tenantId)
                ->expiringSoon($window['days'])
                ->where('quantity_available', '>', 0)
                ->with('product:id,name,sku')
                ->get();

            foreach ($batches as $batch) {
                $daysUntil = $batch->daysUntilExpiry();
                $key = "expiring_lot_{$batch->id}_{$window['days']}";

                if ($this->alertExistsForBatch($tenantId, 'expiring_lot', $batch->id, $window['days'])) {
                    continue;
                }

                SeasonalAlert::create([
                    'tenant_id'     => $tenantId,
                    'type'          => 'expiring_lot',
                    'priority'      => $window['priority'],
                    'title'         => "Lote prox. venc.: {$batch->product?->name} ({$batch->batch_number})",
                    'body'          => "Lote {$batch->batch_number} vence em {$daysUntil} dia(s) com {$batch->quantity_available} unid. disponíveis.",
                    'metadata'      => [
                        'batch_id'           => $batch->id,
                        'product_id'         => $batch->product_id,
                        'batch_number'       => $batch->batch_number,
                        'expiry_date'        => $batch->expiry_date?->toDateString(),
                        'days_until_expiry'  => $daysUntil,
                        'quantity_available' => (float) $batch->quantity_available,
                        'window_days'        => $window['days'],
                    ],
                    'reference_date' => $batch->expiry_date?->toDateString(),
                ]);

                $created++;

                // Suggest promotion for lots nearing expiry with significant stock
                if ($window['days'] <= 15 && $batch->quantity_available > 0) {
                    $this->createPromotionSuggestion($tenantId, $batch, $daysUntil);
                }
            }
        }

        // Expired lots with remaining stock
        $expired = Batch::forTenant($tenantId)
            ->expired()
            ->where('quantity_available', '>', 0)
            ->with('product:id,name,sku')
            ->get();

        foreach ($expired as $batch) {
            if ($this->alertExistsForBatch($tenantId, 'expired_lot', $batch->id)) {
                continue;
            }

            SeasonalAlert::create([
                'tenant_id'     => $tenantId,
                'type'          => 'expired_lot',
                'priority'      => 'critical',
                'title'         => "Lote VENCIDO: {$batch->product?->name} ({$batch->batch_number})",
                'body'          => "Lote {$batch->batch_number} está vencido com {$batch->quantity_available} unid. ainda em estoque. Ação imediata necessária.",
                'metadata'      => [
                    'batch_id'           => $batch->id,
                    'product_id'         => $batch->product_id,
                    'batch_number'       => $batch->batch_number,
                    'expiry_date'        => $batch->expiry_date?->toDateString(),
                    'quantity_available' => (float) $batch->quantity_available,
                ],
                'reference_date' => $batch->expiry_date?->toDateString(),
            ]);

            $created++;
        }

        return $created;
    }

    public function listForTenant(int $tenantId, array $filters = [], int $perPage = 30): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = SeasonalAlert::forTenant($tenantId)->unacknowledged()->latest();

        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }
        if (!empty($filters['priority'])) {
            $query->ofPriority($filters['priority']);
        }

        return $query->paginate($perPage);
    }

    public function acknowledge(int $tenantId, int $alertId, int $userId): SeasonalAlert
    {
        $alert = SeasonalAlert::forTenant($tenantId)->findOrFail($alertId);
        $alert->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
        ]);
        return $alert->fresh();
    }

    public function acknowledgeAll(int $tenantId, int $userId, ?string $type = null): int
    {
        $query = SeasonalAlert::forTenant($tenantId)->unacknowledged();
        if ($type) $query->ofType($type);

        return $query->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
        ]);
    }

    public function summary(int $tenantId): array
    {
        $counts = SeasonalAlert::forTenant($tenantId)
            ->unacknowledged()
            ->selectRaw('type, priority, COUNT(*) as total')
            ->groupBy('type', 'priority')
            ->get();

        return [
            'total_unacknowledged' => $counts->sum('total'),
            'critical'             => $counts->where('priority', 'critical')->sum('total'),
            'high'                 => $counts->where('priority', 'high')->sum('total'),
            'by_type'              => $counts->groupBy('type')->map(fn ($g) => $g->sum('total'))->toArray(),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function alertExistsForBatch(int $tenantId, string $type, int $batchId, ?int $windowDays = null): bool
    {
        $query = SeasonalAlert::forTenant($tenantId)
            ->where('type', $type)
            ->whereJsonContains('metadata->batch_id', $batchId)
            ->unacknowledged();

        if ($windowDays) {
            $query->whereJsonContains('metadata->window_days', $windowDays);
        }

        return $query->exists();
    }

    private function createPromotionSuggestion(int $tenantId, Batch $batch, int $daysUntilExpiry): void
    {
        if ($this->alertExistsForBatch($tenantId, 'promotion_suggestion', $batch->id)) {
            return;
        }

        $suggestedDiscount = $daysUntilExpiry <= 7 ? 40 : 20;

        SeasonalAlert::create([
            'tenant_id' => $tenantId,
            'type'      => 'promotion_suggestion',
            'priority'  => 'high',
            'title'     => "Sugestão de promoção: {$batch->product?->name}",
            'body'      => "Ofereça até {$suggestedDiscount}% de desconto para girar o lote {$batch->batch_number} antes do vencimento em {$daysUntilExpiry} dia(s).",
            'metadata'  => [
                'batch_id'           => $batch->id,
                'product_id'         => $batch->product_id,
                'suggested_discount' => $suggestedDiscount,
                'quantity_available' => (float) $batch->quantity_available,
                'days_until_expiry'  => $daysUntilExpiry,
            ],
            'reference_date' => $batch->expiry_date?->toDateString(),
        ]);
    }
}
