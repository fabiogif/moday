<?php

namespace App\Services;

use App\Models\AccountReceivable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AgingReportService
{
    private const BUCKETS = [
        ['label' => '1-30 dias',  'min' => 1,  'max' => 30],
        ['label' => '31-60 dias', 'min' => 31, 'max' => 60],
        ['label' => '61-90 dias', 'min' => 61, 'max' => 90],
        ['label' => '+90 dias',   'min' => 91, 'max' => null],
    ];

    public function generate(int $tenantId, ?int $clientFilter = null): array
    {
        $today = Carbon::today();

        $overdueReceivables = AccountReceivable::where('tenant_id', $tenantId)
            ->whereIn('status', ['vencido', 'parcial'])
            ->where('due_date', '<', $today)
            ->with('client:id,name,company_name,trade_name,cnpj,cpf')
            ->get();

        $buckets = array_map(
            fn (array $bucket) => $this->buildBucket($bucket, $overdueReceivables, $today),
            self::BUCKETS
        );

        $drillDown = $clientFilter
            ? $this->drillDownByClient($clientFilter, $overdueReceivables, $today)
            : null;

        return [
            'reference_date' => $today->toDateString(),
            'summary' => [
                'total_clients'  => $overdueReceivables->pluck('client_id')->unique()->filter()->count(),
                'total_amount'   => round($overdueReceivables->sum('amount'), 2),
                'total_overdue'  => round($overdueReceivables->sum('amount') - $overdueReceivables->sum('amount_received'), 2),
                'total_records'  => $overdueReceivables->count(),
            ],
            'buckets'    => $buckets,
            'drill_down' => $drillDown,
        ];
    }

    private function buildBucket(array $bucket, Collection $receivables, Carbon $today): array
    {
        $items = $receivables->filter(function ($ar) use ($bucket, $today) {
            $days = (int) Carbon::parse($ar->due_date)->diffInDays($today);
            return $days >= $bucket['min'] && ($bucket['max'] === null || $days <= $bucket['max']);
        });

        return [
            'label'          => $bucket['label'],
            'min_days'       => $bucket['min'],
            'max_days'       => $bucket['max'],
            'count'          => $items->count(),
            'total_amount'   => round($items->sum('amount'), 2),
            'total_received' => round($items->sum('amount_received'), 2),
            'total_overdue'  => round($items->sum('amount') - $items->sum('amount_received'), 2),
            'clients'        => $this->summarizeByClient($items, $today),
        ];
    }

    private function summarizeByClient(Collection $items, Carbon $today): array
    {
        return $items->groupBy('client_id')
            ->map(function ($group) use ($today) {
                $client      = $group->first()?->client;
                $mostOverdue = $group->sortBy('due_date')->first();
                return [
                    'client_id'     => $group->first()?->client_id,
                    'client_name'   => $client?->company_name ?? $client?->trade_name ?? $client?->name ?? 'Não identificado',
                    'document'      => $client?->cnpj ?? $client?->cpf,
                    'count'         => $group->count(),
                    'total_overdue' => round($group->sum('amount') - $group->sum('amount_received'), 2),
                    'oldest_days'   => $mostOverdue ? (int) Carbon::parse($mostOverdue->due_date)->diffInDays($today) : 0,
                ];
            })
            ->sortByDesc('total_overdue')
            ->values()
            ->toArray();
    }

    private function drillDownByClient(int $clientId, Collection $allItems, Carbon $today): array
    {
        return $allItems->where('client_id', $clientId)
            ->map(fn ($ar) => [
                'id'              => $ar->id,
                'description'     => $ar->description,
                'due_date'        => $ar->due_date,
                'amount'          => $ar->amount,
                'amount_received' => $ar->amount_received,
                'balance'         => round($ar->amount - $ar->amount_received, 2),
                'days_overdue'    => (int) Carbon::parse($ar->due_date)->diffInDays($today),
                'status'          => $ar->status,
            ])
            ->values()
            ->toArray();
    }
}
