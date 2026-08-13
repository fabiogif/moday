<?php

namespace Tests\Unit\Visit;

use App\Models\VisitRecurrence;
use App\Services\Visit\VisitRecurrenceDateCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VisitRecurrenceDateCalculatorTest extends TestCase
{
    private VisitRecurrenceDateCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new VisitRecurrenceDateCalculator();
    }

    private function makeRecurrence(array $overrides = []): VisitRecurrence
    {
        return new VisitRecurrence(array_merge([
            'frequency' => 'weekly',
            'interval_count' => 1,
            'days_of_week' => [1],
            'day_of_month' => null,
            'starts_on' => Carbon::parse('2026-07-01'),
            'ends_on' => null,
        ], $overrides));
    }

    #[Test]
    public function daily_frequency_respects_interval(): void
    {
        $recurrence = $this->makeRecurrence(['frequency' => 'daily', 'interval_count' => 2, 'starts_on' => Carbon::parse('2026-07-01')]);

        $dates = $this->calculator->occurrencesWithin($recurrence, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-08'));

        $this->assertSame(['2026-07-01', '2026-07-03', '2026-07-05', '2026-07-07'], $dates);
    }

    #[Test]
    public function weekly_frequency_generates_selected_days_of_week(): void
    {
        // 2026-07-01 é uma quarta-feira (3). Recorrência nas segundas(1) e quintas(4).
        $recurrence = $this->makeRecurrence(['frequency' => 'weekly', 'days_of_week' => [1, 4], 'starts_on' => Carbon::parse('2026-07-01')]);

        $dates = $this->calculator->occurrencesWithin($recurrence, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-14'));

        $this->assertSame(['2026-07-02', '2026-07-06', '2026-07-09', '2026-07-13'], $dates);
    }

    #[Test]
    public function weekly_frequency_respects_interval_of_two_weeks(): void
    {
        $recurrence = $this->makeRecurrence(['frequency' => 'weekly', 'interval_count' => 2, 'days_of_week' => [1], 'starts_on' => Carbon::parse('2026-07-01')]);

        $dates = $this->calculator->occurrencesWithin($recurrence, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-28'));

        // Semana de 2026-07-01 (seg=2026-06-29) é a semana-base; a próxima válida é 2 semanas depois.
        $this->assertSame(['2026-07-13', '2026-07-27'], $dates);
    }

    #[Test]
    public function monthly_frequency_clamps_day_to_shorter_months(): void
    {
        $recurrence = $this->makeRecurrence(['frequency' => 'monthly', 'day_of_month' => 31, 'starts_on' => Carbon::parse('2026-01-31')]);

        $dates = $this->calculator->occurrencesWithin($recurrence, Carbon::parse('2026-01-01'), Carbon::parse('2026-04-30'));

        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'], $dates);
    }

    #[Test]
    public function custom_frequency_generates_no_automatic_occurrences(): void
    {
        $recurrence = $this->makeRecurrence(['frequency' => 'custom']);

        $dates = $this->calculator->occurrencesWithin($recurrence, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

        $this->assertSame([], $dates);
    }

    #[Test]
    public function never_returns_dates_before_starts_on(): void
    {
        $recurrence = $this->makeRecurrence(['frequency' => 'daily', 'interval_count' => 1, 'starts_on' => Carbon::parse('2026-07-10')]);

        $dates = $this->calculator->occurrencesWithin($recurrence, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-12'));

        $this->assertSame(['2026-07-10', '2026-07-11', '2026-07-12'], $dates);
    }

    #[Test]
    public function respects_ends_on_even_when_window_is_larger(): void
    {
        $recurrence = $this->makeRecurrence([
            'frequency' => 'daily',
            'interval_count' => 1,
            'starts_on' => Carbon::parse('2026-07-01'),
            'ends_on' => Carbon::parse('2026-07-03'),
        ]);

        $dates = $this->calculator->occurrencesWithin($recurrence, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

        $this->assertSame(['2026-07-01', '2026-07-02', '2026-07-03'], $dates);
    }
}
