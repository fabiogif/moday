<?php

namespace Tests\Feature\Distribtec\Integration\Phase3;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class UcSec02AuditTrailTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc_sec02_audit_logs_api_returns_recall_entry(): void
    {
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 10,
            'batch_number' => 'AUDIT-001',
        ]);

        $batch = \App\Models\Batch::where('batch_number', 'AUDIT-001')->first();

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/batches/{$batch->id}/recall", ['reason' => 'Teste auditoria'])
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/audit-logs?entity_type=batch&entity_id=' . $batch->id)
            ->assertStatus(200)
            ->assertJsonFragment(['action' => 'batch.recalled']);
    }
}
