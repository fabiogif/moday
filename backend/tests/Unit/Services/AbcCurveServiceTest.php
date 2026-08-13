<?php

namespace Tests\Unit\Services;

use App\Services\AbcCurveService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AbcCurveServiceTest extends TestCase
{
    private AbcCurveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AbcCurveService();
    }

    private function makeRows(array $revenues): Collection
    {
        return collect(array_map(fn ($r) => (object) ['revenue' => $r, 'client_id' => rand()], $revenues));
    }

    #[Test]
    public function classify_assigns_A_to_items_up_to_80_percent(): void
    {
        // 4 items: 50, 30 → cumulative 50%, 80% = class A; 10 → 90% B; 10 → 100% C
        $rows = $this->makeRows([50, 30, 10, 10]);

        $result = $this->service->classify($rows, 100, 'revenue', fn ($r, $p, $c, $class) => [
            'revenue' => $r->revenue,
            'class'   => $class,
        ]);

        $this->assertEquals('A', $result[0]['class']); // 50%
        $this->assertEquals('A', $result[1]['class']); // 80%
        $this->assertEquals('B', $result[2]['class']); // 90%
        $this->assertEquals('C', $result[3]['class']); // 100%
    }

    #[Test]
    public function classify_assigns_B_to_items_between_80_and_95_percent(): void
    {
        // 80, 10, 5, 5 → cumulative 80%, 90%, 95%, 100%
        $rows = $this->makeRows([80, 10, 5, 5]);

        $result = $this->service->classify($rows, 100, 'revenue', fn ($r, $p, $c, $class) => [
            'revenue' => $r->revenue,
            'class'   => $class,
        ]);

        $this->assertEquals('A', $result[0]['class']); // 80 — exactly on boundary = A
        $this->assertEquals('B', $result[1]['class']); // 90%
        $this->assertEquals('B', $result[2]['class']); // 95% — exactly on boundary = B
        $this->assertEquals('C', $result[3]['class']); // 100%
    }

    #[Test]
    public function classify_handles_zero_total_without_division_by_zero(): void
    {
        $rows = $this->makeRows([0, 0]);

        $result = $this->service->classify($rows, 0, 'revenue', fn ($r, $p, $c, $class) => [
            'revenue' => $r->revenue,
            'class'   => $class,
            'percent' => $p,
        ]);

        foreach ($result as $item) {
            $this->assertEquals(0, $item['percent']);
        }
    }

    #[Test]
    public function classify_is_cumulative_across_items(): void
    {
        // Two items 50/50: first = 50%, second = 100%
        $rows = $this->makeRows([50, 50]);

        $result = $this->service->classify($rows, 100, 'revenue', fn ($r, $p, $c, $class) => [
            'revenue'            => $r->revenue,
            'revenue_percent'    => $p,
            'cumulative_percent' => $c,
            'class'              => $class,
        ]);

        $this->assertEquals(50.0, $result[0]['cumulative_percent']);
        $this->assertEquals(100.0, $result[1]['cumulative_percent']);
    }

    #[Test]
    public function buildSummary_counts_and_sums_by_class(): void
    {
        $classified = collect([
            ['class' => 'A', 'revenue' => 50],
            ['class' => 'A', 'revenue' => 30],
            ['class' => 'B', 'revenue' => 10],
            ['class' => 'C', 'revenue' => 10],
        ]);

        $summary = $this->service->buildSummary($classified, 'revenue');

        $this->assertEquals(2, $summary['A']['count']);
        $this->assertEquals(80.0, $summary['A']['revenue']);
        $this->assertEquals(1, $summary['B']['count']);
        $this->assertEquals(1, $summary['C']['count']);
    }

    #[Test]
    public function parseDateRange_uses_defaults_when_null(): void
    {
        [$start, $end] = $this->service->parseDateRange(null, null);

        $this->assertEquals(now()->startOfYear()->toDateString(), $start->toDateString());
        $this->assertEquals(now()->endOfMonth()->toDateString(), $end->toDateString());
    }

    #[Test]
    public function parseDateRange_parses_provided_dates(): void
    {
        [$start, $end] = $this->service->parseDateRange('2024-01-01', '2024-06-30');

        $this->assertEquals('2024-01-01', $start->toDateString());
        $this->assertEquals('2024-06-30', $end->toDateString());
    }
}
