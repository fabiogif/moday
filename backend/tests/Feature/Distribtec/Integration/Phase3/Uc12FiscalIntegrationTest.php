<?php

namespace Tests\Feature\Distribtec\Integration\Phase3;

use App\Models\SaleOrder;
use App\Models\Tenant;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc12FiscalIntegrationTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc12_request_fiscal_emission_for_billed_order(): void
    {
        $this->tenant->update([
            'fiscal_integration_provider' => 'focusnfe',
            'fiscal_integration_config'   => ['api_key' => 'test-key', 'webhook_secret' => 'wh-secret'],
        ]);

        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'faturado',
            'total'     => 500,
        ]);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$order->id}/fiscal/request")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.provider', 'focusnfe');

        $this->assertEquals('pending', $order->fresh()->nfe_status);
    }

    #[Test]
    public function uc12_webhook_updates_nfe_status(): void
    {
        $secret = 'webhook-test-secret';
        $this->tenant->update([
            'fiscal_integration_provider' => 'focusnfe',
            'fiscal_integration_config'   => ['webhook_secret' => $secret],
        ]);

        $order = SaleOrder::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'status'     => 'faturado',
            'nfe_status' => 'pending',
        ]);

        $this->postJson("/api/webhooks/fiscal/{$this->tenant->id}", [
            'sale_order_id' => $order->id,
            'status'        => 'authorized',
            'nfe_number'    => '12345',
            'nfe_key'       => '35260612345678901234567890123456789012345678',
            'nfe_series'    => '1',
        ], ['X-Fiscal-Webhook-Token' => $secret])
            ->assertStatus(202);

        $order->refresh();
        $this->assertEquals('authorized', $order->nfe_status);
        $this->assertEquals('12345', $order->nfe_number);
        $this->assertNotNull($order->nfe_issued_at);

        $this->assertDatabaseHas('fiscal_webhook_events', [
            'tenant_id'     => $this->tenant->id,
            'sale_order_id' => $order->id,
            'status'        => 'authorized',
        ]);
    }

    #[Test]
    public function uc12_rejects_fiscal_request_when_not_billed(): void
    {
        $this->tenant->update(['fiscal_integration_provider' => 'focusnfe']);

        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'separacao',
        ]);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$order->id}/fiscal/request")
            ->assertStatus(422);
    }
}
