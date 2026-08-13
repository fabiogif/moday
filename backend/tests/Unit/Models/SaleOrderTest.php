<?php

namespace Tests\Unit\Models;

use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaleOrderTest extends TestCase
{
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $plan         = Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function it_auto_generates_uuid_and_identify_on_create(): void
    {
        $order = SaleOrder::create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'orcamento',
            'subtotal'  => 100,
            'total'     => 100,
        ]);

        $this->assertNotNull($order->uuid);
        $this->assertStringStartsWith('PV-', $order->identify);
        $this->assertNotNull($order->ordered_at);
    }

    #[Test]
    public function it_returns_correct_next_status(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'orcamento']);
        $this->assertEquals('aprovado', $order->nextStatus());

        $order->status = 'aprovado';
        $this->assertEquals('separacao', $order->nextStatus());

        $order->status = 'separacao';
        $this->assertEquals('faturado', $order->nextStatus());

        $order->status = 'faturado';
        $this->assertEquals('em_transito', $order->nextStatus());

        $order->status = 'em_transito';
        $this->assertEquals('entregue', $order->nextStatus());
    }

    #[Test]
    public function it_returns_null_next_status_for_terminal_states(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'entregue']);
        $this->assertNull($order->nextStatus());

        $order->status = 'cancelado';
        $this->assertNull($order->nextStatus());
    }

    #[Test]
    public function it_can_advance_status(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'orcamento']);

        $this->assertTrue($order->canAdvance());
        $result = $order->advanceStatus($this->user->id);

        $this->assertTrue($result);
        $this->assertEquals('aprovado', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->approved_at);
    }

    #[Test]
    public function it_cannot_advance_from_terminal_status(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'entregue']);

        $this->assertFalse($order->canAdvance());
        $result = $order->advanceStatus($this->user->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function scope_for_tenant_filters_correctly(): void
    {
        SaleOrder::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $otherPlan   = Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $otherPlan->id]);
        SaleOrder::factory()->create(['tenant_id' => $otherTenant->id]);

        $orders = SaleOrder::forTenant($this->tenant->id)->get();
        $this->assertCount(3, $orders);
        $this->assertTrue($orders->every(fn($o) => $o->tenant_id === $this->tenant->id));
    }

    #[Test]
    public function scope_not_archived_excludes_archived_orders(): void
    {
        SaleOrder::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);
        SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'archived_at' => now()]);

        $orders = SaleOrder::forTenant($this->tenant->id)->notArchived()->get();
        $this->assertCount(2, $orders);
    }
}
