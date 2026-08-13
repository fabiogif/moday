<?php

namespace Tests\Unit\Services;

use App\Repositories\Contracts\AdminMetricsRepositoryInterface;
use App\Services\AdminMetricsService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminMetricsServiceTest extends TestCase
{
    #[Test]
    public function it_delegates_revenue_metrics_to_repository(): void
    {
        $repository = Mockery::mock(AdminMetricsRepositoryInterface::class);
        $repository->shouldReceive('getTotalMrr')->once()->andReturn(1500.0);
        $repository->shouldReceive('getRevenueByMonth')->once()->andReturn(collect([]));
        $repository->shouldReceive('getRevenueByPlan')->once()->andReturn(collect([]));
        $repository->shouldReceive('getTopTenantsByMrr')->once()->andReturn(collect([]));

        $service = new AdminMetricsService($repository);
        $result = $service->getRevenueMetrics(now()->subMonth(), now());

        $this->assertSame(1500.0, $result['mrr']);
        $this->assertArrayHasKey('revenue_by_month', $result);
        $this->assertArrayHasKey('by_plan', $result);
        $this->assertArrayHasKey('top_tenants', $result);
    }

    #[Test]
    public function it_calculates_message_costs_in_service(): void
    {
        $repository = Mockery::mock(AdminMetricsRepositoryInterface::class);
        $repository->shouldReceive('getMessagesByDay')->once()->andReturn(collect([]));
        $repository->shouldReceive('getMessageTotals')->once()->andReturn((object) [
            'total' => 100,
            'whatsapp' => 50,
            'email' => 30,
            'sms' => 20,
        ]);
        $repository->shouldReceive('getTopMessageSenders')->once()->andReturn(collect([]));
        $repository->shouldReceive('getTenantsNearMessageLimit')->once()->andReturn(collect([]));

        $service = new AdminMetricsService($repository);
        $result = $service->getMessageMetrics(now()->subDays(30), now());

        $this->assertEquals(2.5, $result['costs']['whatsapp']);
        $this->assertEquals(6.0, $result['costs']['sms']);
        $this->assertEquals(8.5, $result['costs']['total']);
    }
}
