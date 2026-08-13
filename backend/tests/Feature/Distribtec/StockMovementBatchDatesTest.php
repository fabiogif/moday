<?php

namespace Tests\Feature\Distribtec;

use App\Models\Batch;
use App\Models\Plan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class StockMovementBatchDatesTest extends TestCase
{
    use GrantsDistribtecPermissions;

    private User $user;
    private Tenant $tenant;
    private string $token;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $plan         = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantDistribtecPermissions($this->user, $this->tenant->id);
        $this->token     = JWTAuth::fromUser($this->user);
        $this->warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function entrada_persists_batch_with_manufacture_and_expiry_dates(): void
    {
        $product = Product::factory()->create([
            'tenant_id'             => $this->tenant->id,
            'requires_prescription' => false,
            'controlled_substance'  => false,
        ]);

        $this->withHeaders($this->auth())
            ->postJson('/api/stock-movements', [
                'type'             => 'entrada',
                'product_id'       => $product->id,
                'warehouse_id'     => $this->warehouse->id,
                'quantity'         => 40,
                'batch_number'     => 'LOT-FAB-001',
                'manufacture_date' => '2026-01-10',
                'expiry_date'      => '2027-06-30',
            ])
            ->assertStatus(201);

        $batch = Batch::where('batch_number', 'LOT-FAB-001')->first();
        $this->assertNotNull($batch);
        $this->assertEquals('2026-01-10', $batch->manufacture_date?->toDateString());
        $this->assertEquals('2027-06-30', $batch->expiry_date?->toDateString());
        $this->assertEquals($this->tenant->id, $batch->tenant_id);
        $this->assertEquals($product->id, $batch->product_id);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id'  => $this->tenant->id,
            'product_id' => $product->id,
            'batch_id'   => $batch->id,
            'type'       => 'entrada',
            'quantity'   => 40,
        ]);
    }

    #[Test]
    public function list_includes_batch_manufacture_and_expiry_dates(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $batch   = Batch::factory()->create([
            'tenant_id'        => $this->tenant->id,
            'product_id'       => $product->id,
            'warehouse_id'     => $this->warehouse->id,
            'batch_number'     => 'LOT-LIST-1',
            'manufacture_date' => '2025-12-01',
            'expiry_date'      => '2026-12-01',
        ]);

        StockMovement::create([
            'tenant_id'    => $this->tenant->id,
            'product_id'   => $product->id,
            'batch_id'     => $batch->id,
            'warehouse_id' => $this->warehouse->id,
            'performed_by' => $this->user->id,
            'type'         => 'entrada',
            'quantity'     => 10,
        ]);

        $response = $this->withHeaders($this->auth())
            ->getJson('/api/stock-movements')
            ->assertStatus(200)
            ->assertJsonPath('data.0.batch.batch_number', 'LOT-LIST-1');

        $manufacture = (string) $response->json('data.0.batch.manufacture_date');
        $expiry      = (string) $response->json('data.0.batch.expiry_date');
        $this->assertStringStartsWith('2025-12-01', $manufacture);
        $this->assertStringStartsWith('2026-12-01', $expiry);
    }

    /**
     * Regressão: listagem quebrava no frontend (`Cannot read properties of
     * null (reading 'name')`) quando o produto de uma movimentação era
     * excluído (soft delete) depois — a relação `product` vinha `null` sem
     * `withTrashed()`. Ver `/stock-movements`.
     */
    #[Test]
    public function list_still_shows_product_after_product_is_soft_deleted(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        StockMovement::create([
            'tenant_id'    => $this->tenant->id,
            'product_id'   => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'performed_by' => $this->user->id,
            'type'         => 'entrada',
            'quantity'     => 10,
        ]);

        $product->delete();

        $this->withHeaders($this->auth())
            ->getJson('/api/stock-movements')
            ->assertStatus(200)
            ->assertJsonPath('data.0.product.id', $product->id)
            ->assertJsonPath('data.0.product.name', $product->name);
    }

    #[Test]
    public function controlled_product_requires_manufacture_date_on_entrada(): void
    {
        $product = Product::factory()->create([
            'tenant_id'            => $this->tenant->id,
            'controlled_substance' => true,
        ]);

        $this->withHeaders($this->auth())
            ->postJson('/api/stock-movements', [
                'type'         => 'entrada',
                'product_id'   => $product->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity'     => 5,
                'batch_number' => 'CTRL-001',
                'expiry_date'  => now()->addYear()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'A data de fabricação é obrigatória para produtos controlados ou que exigem receita.',
            ]);
    }

    #[Test]
    public function prescription_product_accepts_entrada_with_manufacture_date(): void
    {
        $product = Product::factory()->create([
            'tenant_id'             => $this->tenant->id,
            'requires_prescription' => true,
        ]);

        $this->withHeaders($this->auth())
            ->postJson('/api/stock-movements', [
                'type'             => 'entrada',
                'product_id'       => $product->id,
                'warehouse_id'     => $this->warehouse->id,
                'quantity'         => 8,
                'batch_number'     => 'RX-001',
                'manufacture_date' => now()->subMonths(2)->toDateString(),
                'expiry_date'      => now()->addYear()->toDateString(),
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('batches', [
            'batch_number' => 'RX-001',
            'product_id'   => $product->id,
        ]);
    }

    #[Test]
    public function rejects_manufacture_date_after_expiry_date(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->postJson('/api/stock-movements', [
                'type'             => 'entrada',
                'product_id'       => $product->id,
                'warehouse_id'     => $this->warehouse->id,
                'quantity'         => 3,
                'batch_number'     => 'BAD-DATES',
                'manufacture_date' => '2027-01-01',
                'expiry_date'      => '2026-01-01',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'A data de fabricação não pode ser posterior à validade.',
            ]);
    }

    #[Test]
    public function regular_product_allows_entrada_without_manufacture_date(): void
    {
        $product = Product::factory()->create([
            'tenant_id'             => $this->tenant->id,
            'requires_prescription' => false,
            'controlled_substance'  => false,
        ]);

        $this->withHeaders($this->auth())
            ->postJson('/api/stock-movements', [
                'type'         => 'entrada',
                'product_id'   => $product->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity'     => 12,
                'batch_number' => 'OPT-FAB',
                'expiry_date'  => now()->addMonths(6)->toDateString(),
            ])
            ->assertStatus(201);
    }
}
