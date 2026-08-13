<?php

namespace Tests\Unit\Services\Commercial;

use App\Models\Client;
use App\Models\PriceTable;
use App\Models\PriceTableItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\Commercial\PriceTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceTableServiceTest extends TestCase
{
    use RefreshDatabase;

    private PriceTableService $service;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PriceTableService::class);
        $this->tenant  = Tenant::factory()->create();
    }

    // -------------------------------------------------------------------------
    // listForTenant
    // -------------------------------------------------------------------------

    public function test_list_for_tenant_returns_only_own_tables(): void
    {
        PriceTable::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);
        PriceTable::factory()->create(); // outro tenant

        $result = $this->service->listForTenant($this->tenant->id);

        $this->assertCount(2, $result);
        $result->each(fn ($t) => $this->assertEquals($this->tenant->id, $t->tenant_id));
    }

    public function test_list_for_tenant_includes_items_count(): void
    {
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        PriceTableItem::create([
            'price_table_id' => $table->id,
            'product_id'     => $product->id,
            'price'          => 10.00,
            'min_quantity'   => 1,
        ]);

        $result = $this->service->listForTenant($this->tenant->id);

        $this->assertEquals(1, $result->first()->items_count);
    }

    public function test_list_for_tenant_returns_empty_when_no_tables(): void
    {
        $result = $this->service->listForTenant($this->tenant->id);
        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_persists_table_with_items(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $table = $this->service->create($this->tenant->id, [
            'name'      => 'Tabela Atacado',
            'type'      => 'padrao',
            'is_active' => true,
            'items'     => [
                ['product_id' => $product->id, 'price' => 25.50, 'min_quantity' => 1],
            ],
        ]);

        $this->assertDatabaseHas('price_tables', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Tabela Atacado',
        ]);
        $this->assertDatabaseHas('price_table_items', [
            'price_table_id' => $table->id,
            'product_id'     => $product->id,
            'price'          => 25.50,
        ]);
    }

    public function test_create_without_items_creates_empty_table(): void
    {
        $table = $this->service->create($this->tenant->id, [
            'name' => 'Tabela Vazia',
            'type' => 'canal',
        ]);

        $this->assertDatabaseHas('price_tables', ['name' => 'Tabela Vazia']);
        $this->assertCount(0, $table->items);
    }

    public function test_create_returns_table_with_items_loaded(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $table = $this->service->create($this->tenant->id, [
            'name'  => 'Tabela',
            'items' => [
                ['product_id' => $product->id, 'price' => 9.99, 'min_quantity' => 1],
            ],
        ]);

        $this->assertTrue($table->relationLoaded('items'));
        $this->assertEquals(9.99, $table->items->first()->price);
    }

    public function test_create_multiple_items_for_volume_pricing(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $table = $this->service->create($this->tenant->id, [
            'name'  => 'Volume',
            'type'  => 'volume',
            'items' => [
                ['product_id' => $product->id, 'price' => 20.00, 'min_quantity' => 1],
                ['product_id' => $product->id, 'price' => 15.00, 'min_quantity' => 10],
                ['product_id' => $product->id, 'price' => 10.00, 'min_quantity' => 50],
            ],
        ]);

        $this->assertCount(3, $table->items);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_replaces_all_items(): void
    {
        $product1 = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $product2 = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $table = $this->service->create($this->tenant->id, [
            'name'  => 'Original',
            'items' => [['product_id' => $product1->id, 'price' => 10.00, 'min_quantity' => 1]],
        ]);

        $this->service->update($table, [
            'name'  => 'Atualizado',
            'items' => [['product_id' => $product2->id, 'price' => 99.00, 'min_quantity' => 1]],
        ]);

        $this->assertDatabaseMissing('price_table_items', ['product_id' => $product1->id, 'price_table_id' => $table->id]);
        $this->assertDatabaseHas('price_table_items', ['product_id' => $product2->id, 'price' => 99.00]);
    }

    public function test_update_name_without_items_keeps_existing_items(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $table   = $this->service->create($this->tenant->id, [
            'name'  => 'Original',
            'items' => [['product_id' => $product->id, 'price' => 5.00, 'min_quantity' => 1]],
        ]);

        $this->service->update($table, ['name' => 'Novo Nome']);

        $this->assertDatabaseHas('price_tables', ['id' => $table->id, 'name' => 'Novo Nome']);
        $this->assertDatabaseHas('price_table_items', ['price_table_id' => $table->id, 'product_id' => $product->id]);
    }

    public function test_update_returns_fresh_table_with_items(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);

        $result = $this->service->update($table, [
            'name'  => 'Novo',
            'items' => [['product_id' => $product->id, 'price' => 3.00, 'min_quantity' => 1]],
        ]);

        $this->assertTrue($result->relationLoaded('items'));
        $this->assertEquals('Novo', $result->name);
    }

    // -------------------------------------------------------------------------
    // getPriceForProduct (model method exercised via service)
    // -------------------------------------------------------------------------

    public function test_get_price_for_product_returns_correct_price(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 30.00, 'min_quantity' => 1]);

        $price = $table->getPriceForProduct($product->id, 1);

        $this->assertEquals(30.00, $price);
    }

    public function test_get_price_for_product_picks_best_tier_for_quantity(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 20.00, 'min_quantity' => 1]);
        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 15.00, 'min_quantity' => 10]);

        $this->assertEquals(15.00, $table->getPriceForProduct($product->id, 10));
        $this->assertEquals(20.00, $table->getPriceForProduct($product->id, 5));
    }

    public function test_get_price_for_product_returns_null_when_not_found(): void
    {
        $table = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNull($table->getPriceForProduct(9999, 1));
    }

    // -------------------------------------------------------------------------
    // lookupPrice
    // -------------------------------------------------------------------------

    public function test_lookup_price_uses_client_specific_table(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'cliente_especifico']);
        $client  = Client::factory()->create(['tenant_id' => $this->tenant->id, 'price_table_id' => $table->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 50.00, 'min_quantity' => 1]);

        $price = $this->service->lookupPrice($client->id, $product->id, 1);

        $this->assertEquals(50.00, $price);
    }

    public function test_lookup_price_falls_back_to_default_table(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 100.00]);
        $client  = Client::factory()->create(['tenant_id' => $this->tenant->id, 'price_table_id' => null]);
        $default = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'padrao', 'is_active' => true]);

        PriceTableItem::create(['price_table_id' => $default->id, 'product_id' => $product->id, 'price' => 80.00, 'min_quantity' => 1]);

        $price = $this->service->lookupPrice($client->id, $product->id, 1);

        $this->assertEquals(80.00, $price);
    }

    public function test_lookup_price_returns_null_when_no_table_exists(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $price = $this->service->lookupPrice(null, $product->id, 1);

        $this->assertNull($price);
    }

    public function test_lookup_price_returns_null_for_inactive_table(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'padrao', 'is_active' => false]);
        $client  = Client::factory()->create(['tenant_id' => $this->tenant->id, 'price_table_id' => $table->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 10.00, 'min_quantity' => 1]);

        $price = $this->service->lookupPrice($client->id, $product->id, 1);

        $this->assertNull($price);
    }

    // -------------------------------------------------------------------------
    // resolveUnitPrice
    // -------------------------------------------------------------------------

    public function test_resolve_unit_price_returns_table_price_when_found(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 100.00]);
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'padrao']);
        $client  = Client::factory()->create(['tenant_id' => $this->tenant->id, 'price_table_id' => $table->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 75.00, 'min_quantity' => 1]);

        $price = $this->service->resolveUnitPrice($client->id, $product->id, 1);

        $this->assertEquals(75.00, $price);
    }

    public function test_resolve_unit_price_uses_fallback_price_when_no_table(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 100.00]);

        $price = $this->service->resolveUnitPrice(null, $product->id, 1, fallbackPrice: 55.00);

        $this->assertEquals(55.00, $price);
    }

    public function test_resolve_unit_price_uses_product_base_price_as_last_resort(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 42.00]);

        $price = $this->service->resolveUnitPrice(null, $product->id, 1);

        $this->assertEquals(42.00, $price);
    }

    public function test_resolve_unit_price_returns_zero_for_unknown_product(): void
    {
        $price = $this->service->resolveUnitPrice(null, 99999, 1);
        $this->assertEquals(0.0, $price);
    }

    // -------------------------------------------------------------------------
    // applyPricesToItems
    // -------------------------------------------------------------------------

    public function test_apply_prices_sets_price_from_table(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'padrao']);
        $client  = Client::factory()->create(['tenant_id' => $this->tenant->id, 'price_table_id' => $table->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 18.00, 'min_quantity' => 1]);

        $items  = [['product_id' => $product->id, 'quantity' => 2]];
        $result = $this->service->applyPricesToItems($client->id, $items);

        $this->assertEquals(18.00, $result[0]['unit_price']);
    }

    public function test_apply_prices_skips_bonificacao_items(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $items  = [['product_id' => $product->id, 'quantity' => 5, 'item_type' => 'bonificacao']];
        $result = $this->service->applyPricesToItems(null, $items);

        $this->assertEquals(0, $result[0]['unit_price']);
    }

    public function test_apply_prices_keeps_existing_unit_price(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $items  = [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 99.99]];
        $result = $this->service->applyPricesToItems(null, $items);

        $this->assertEquals(99.99, $result[0]['unit_price']);
    }

    public function test_apply_prices_handles_multiple_items(): void
    {
        $product1 = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $product2 = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $table    = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'padrao']);
        $client   = Client::factory()->create(['tenant_id' => $this->tenant->id, 'price_table_id' => $table->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product1->id, 'price' => 10.00, 'min_quantity' => 1]);
        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product2->id, 'price' => 20.00, 'min_quantity' => 1]);

        $result = $this->service->applyPricesToItems($client->id, [
            ['product_id' => $product1->id, 'quantity' => 1],
            ['product_id' => $product2->id, 'quantity' => 3],
        ]);

        $this->assertEquals(10.00, $result[0]['unit_price']);
        $this->assertEquals(20.00, $result[1]['unit_price']);
    }
}
