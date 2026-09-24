<?php

namespace Tests\Unit\Services;

use App\Models\StoreHour;
use App\Models\Tenant;
use App\Services\StoreHourService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreHourServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private StoreHourService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Quarta-feira (day_of_week = 3), 19:00
        Carbon::setTestNow(Carbon::parse('2026-09-23 19:00', config('app.timezone')));

        $this->tenant = Tenant::factory()->create();
        $this->service = app(StoreHourService::class);
    }

    private function period(string $type, string $start, string $end, array $extra = []): StoreHour
    {
        return StoreHour::factory()->forDay(3)->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'delivery_type' => $type,
            'start_time' => $start,
            'end_time' => $end,
        ], $extra));
    }

    #[Test]
    public function accepts_orders_when_no_hours_are_configured()
    {
        $this->assertTrue($this->service->acceptsOrdersNow($this->tenant->id, 'delivery'));
    }

    #[Test]
    public function accepts_orders_when_always_open()
    {
        StoreHour::factory()->alwaysOpen()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($this->service->acceptsOrdersNow($this->tenant->id, 'delivery'));
    }

    #[Test]
    public function rejects_orders_outside_all_periods()
    {
        $this->period('both', '08:00', '12:00');

        $this->assertFalse($this->service->acceptsOrdersNow($this->tenant->id, 'delivery'));
        $this->assertFalse($this->service->acceptsOrdersNow($this->tenant->id, 'pickup'));
    }

    #[Test]
    public function inactive_hours_count_as_not_configured()
    {
        $this->period('both', '08:00', '12:00', ['is_active' => false]);

        $this->assertTrue($this->service->acceptsOrdersNow($this->tenant->id, 'delivery'));
    }

    #[Test]
    public function period_typed_both_accepts_any_shipping_method()
    {
        $this->period('both', '18:00', '23:00');

        $this->assertTrue($this->service->acceptsOrdersNow($this->tenant->id, 'delivery'));
        $this->assertTrue($this->service->acceptsOrdersNow($this->tenant->id, 'pickup'));
    }

    #[Test]
    public function pickup_only_period_rejects_delivery()
    {
        $this->period('pickup', '18:00', '23:00');

        $this->assertFalse($this->service->acceptsOrdersNow($this->tenant->id, 'delivery'));
        $this->assertTrue($this->service->acceptsOrdersNow($this->tenant->id, 'pickup'));
    }

    #[Test]
    public function second_period_of_the_day_counts()
    {
        $this->period('both', '11:00', '14:00', ['start_time_2' => '18:00', 'end_time_2' => '23:00']);

        $this->assertTrue($this->service->acceptsOrdersNow($this->tenant->id, 'delivery'));
    }
}
