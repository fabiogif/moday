<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AbcCurveService
{
    /**
     * Classifies a pre-ordered collection into A/B/C by cumulative percentage.
     * A ≤ 80%, B ≤ 95%, C = rest.
     *
     * @param Collection $rows     Rows sorted descending by the value column.
     * @param float      $total    Grand total for percentage calculation.
     * @param string     $valueKey Column name containing the numeric value (e.g. 'revenue', 'volume').
     * @param callable   $mapper   Maps a row + cumulative percent + class → output array.
     */
    public function classify(Collection $rows, float $total, string $valueKey, callable $mapper): Collection
    {
        $cumulative = 0.0;

        return $rows->map(function ($row) use ($total, $valueKey, $mapper, &$cumulative) {
            $value      = (float) $row->{$valueKey};
            $percent    = $total > 0 ? ($value / $total) * 100 : 0;
            $cumulative += $percent;
            $class       = $cumulative <= 80 ? 'A' : ($cumulative <= 95 ? 'B' : 'C');

            return $mapper($row, round($percent, 2), round($cumulative, 2), $class);
        });
    }

    public function buildSummary(Collection $classified, string $valueKey): array
    {
        return [
            'A' => ['count' => $classified->where('class', 'A')->count(), $valueKey => round($classified->where('class', 'A')->sum($valueKey), 2)],
            'B' => ['count' => $classified->where('class', 'B')->count(), $valueKey => round($classified->where('class', 'B')->sum($valueKey), 2)],
            'C' => ['count' => $classified->where('class', 'C')->count(), $valueKey => round($classified->where('class', 'C')->sum($valueKey), 2)],
        ];
    }

    public function parseDateRange(string|null $start, string|null $end): array
    {
        return [
            Carbon::parse($start ?? now()->startOfYear()),
            Carbon::parse($end   ?? now()->endOfMonth()),
        ];
    }
}
