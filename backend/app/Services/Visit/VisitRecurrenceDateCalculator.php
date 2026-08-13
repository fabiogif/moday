<?php

namespace App\Services\Visit;

use App\Models\VisitRecurrence;
use Carbon\Carbon;

/**
 * Máquina stateless (mesmo espírito de VisitStatusMachine): calcula as datas de
 * ocorrência de uma recorrência dentro de uma janela, sem tocar no banco.
 * A cadência (interval_count) é sempre contada a partir de starts_on, não da
 * janela pedida, para que chamadas repetidas de "gerar próximas ocorrências"
 * produzam sempre a mesma série.
 */
class VisitRecurrenceDateCalculator
{
    /**
     * @return string[] datas Y-m-d, em ordem crescente
     */
    public function occurrencesWithin(VisitRecurrence $recurrence, Carbon $windowStart, Carbon $windowEnd): array
    {
        $rangeStart = $recurrence->starts_on->greaterThan($windowStart) ? $recurrence->starts_on->copy() : $windowStart->copy();
        $rangeEnd = $recurrence->ends_on && $recurrence->ends_on->lessThan($windowEnd) ? $recurrence->ends_on->copy() : $windowEnd->copy();

        if ($rangeStart->greaterThan($rangeEnd)) {
            return [];
        }

        $dates = match ($recurrence->frequency) {
            'daily' => $this->dailyOccurrences($recurrence, $rangeStart, $rangeEnd),
            'weekly' => $this->weeklyOccurrences($recurrence, $rangeStart, $rangeEnd),
            'monthly' => $this->monthlyOccurrences($recurrence, $rangeStart, $rangeEnd),
            default => [],
        };

        sort($dates);

        return $dates;
    }

    private function dailyOccurrences(VisitRecurrence $recurrence, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $interval = max(1, (int) $recurrence->interval_count);
        $dates = [];

        $cursor = $recurrence->starts_on->copy();
        while ($cursor->lessThanOrEqualTo($rangeEnd)) {
            if ($cursor->greaterThanOrEqualTo($rangeStart)) {
                $dates[] = $cursor->format('Y-m-d');
            }
            $cursor->addDays($interval);
        }

        return $dates;
    }

    private function weeklyOccurrences(VisitRecurrence $recurrence, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $interval = max(1, (int) $recurrence->interval_count);
        $daysOfWeek = $recurrence->days_of_week ?? [];
        if ($daysOfWeek === []) {
            return [];
        }

        $dates = [];
        $weekCursor = $recurrence->starts_on->copy()->startOfWeek(Carbon::SUNDAY);
        $baseWeekStart = $weekCursor->copy();

        while ($weekCursor->lessThanOrEqualTo($rangeEnd)) {
            $weeksSinceStart = $baseWeekStart->diffInWeeks($weekCursor);

            if ($weeksSinceStart % $interval === 0) {
                foreach ($daysOfWeek as $dayOfWeek) {
                    $occurrence = $weekCursor->copy()->addDays((int) $dayOfWeek);
                    if ($occurrence->greaterThanOrEqualTo($recurrence->starts_on)
                        && $occurrence->greaterThanOrEqualTo($rangeStart)
                        && $occurrence->lessThanOrEqualTo($rangeEnd)
                    ) {
                        $dates[] = $occurrence->format('Y-m-d');
                    }
                }
            }

            $weekCursor->addWeek();
        }

        return $dates;
    }

    private function monthlyOccurrences(VisitRecurrence $recurrence, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $interval = max(1, (int) $recurrence->interval_count);
        $dayOfMonth = (int) ($recurrence->day_of_month ?? $recurrence->starts_on->day);
        $dates = [];

        $monthCursor = $recurrence->starts_on->copy()->startOfMonth();

        while ($monthCursor->lessThanOrEqualTo($rangeEnd)) {
            $day = min($dayOfMonth, $monthCursor->daysInMonth);
            $occurrence = $monthCursor->copy()->day($day);

            if ($occurrence->greaterThanOrEqualTo($recurrence->starts_on)
                && $occurrence->greaterThanOrEqualTo($rangeStart)
                && $occurrence->lessThanOrEqualTo($rangeEnd)
            ) {
                $dates[] = $occurrence->format('Y-m-d');
            }

            $monthCursor->addMonths($interval);
        }

        return $dates;
    }
}
