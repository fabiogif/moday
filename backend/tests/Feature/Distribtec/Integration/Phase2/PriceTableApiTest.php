<?php

namespace Tests\Feature\Distribtec\Integration\Phase2;

use App\Models\Client;
use App\Models\PriceTable;
use App\Models\PriceTableItem;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class PriceTableApiTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    // -------------------------------------------------------------------------
    // GET /api/price-tables
    // -------------------------------------------------------------------------

    #[Test]
    public function index_returns_tables_for_authenticated_tenant(): void
    {
        PriceTable::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);
        PriceTable::factory()->create(); // outro tenant

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/price-tables')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function index_requires_authentication(): void
    {
        $this->getJson('/api/price-tables')->assertUnauthorized();
    }

    #[Test]
    public function index_returns_empty_when_no_tables(): void
    {
        $this->withHeaders($this->authHeaders())
            ->getJson('/api/price-tables')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // -------------------------------------------------------------------------
    // POST /api/price-tables
    // -------------------------------------------------------------------------

    #[Test]
    public function store_creates_table_without_items(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/price-tables', [
                'name'      => 'Tabela Padrão',
                'type'      => 'padrao',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Tabela Padrão');

        $this->assertDatabaseHas('price_tables', [
            'name'      => 'Tabela Padrão',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function store_creates_table_with_items(): void
    {
        $product = $this->createProduct(['price' => 100.00]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/price-tables', [
                'name'  => 'Com Itens',
                'type'  => 'volume',
                'items' => [
                    ['product_id' => $product->id, 'price' => 80.00, 'min_quantity' => 1],
                    ['product_id' => $product->id, 'price' => 60.00, 'min_quantity' => 10],
                ],
            ])
            ->assertCreated();

        $tableId = $response->json('data.id');
        $this->assertDatabaseHas('price_table_items', ['price_table_id' => $tableId, 'price' => 80.00]);
        $this->assertDatabaseHas('price_table_items', ['price_table_id' => $tableId, 'price' => 60.00]);
    }

    #[Test]
    public function store_fails_validation_without_name(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/price-tables', ['type' => 'padrao'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function store_fails_validation_with_invalid_type(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/price-tables', ['name' => 'Tabela', 'type' => 'invalido'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function store_fails_validation_with_negative_price_on_item(): void
    {
        $product = $this->createProduct();

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/price-tables', [
                'name'  => 'Tabela',
                'items' => [['product_id' => $product->id, 'price' => -1.00, 'min_quantity' => 1]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.price']);
    }

    #[Test]
    public function store_requires_authentication(): void
    {
        $this->postJson('/api/price-tables', ['name' => 'Tabela'])
            ->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // GET /api/price-tables/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function show_returns_table_with_items(): void
    {
        $product = $this->createProduct();
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);

        PriceTableItem::create([
            'price_table_id' => $table->id,
            'product_id'     => $product->id,
            'price'          => 50.00,
            'min_quantity'   => 1,
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/price-tables/{$table->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $table->id)
            ->assertJsonCount(1, 'data.items');
    }

    #[Test]
    public function show_returns_404_for_other_tenant_table(): void
    {
        $otherTable = PriceTable::factory()->create();

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/price-tables/{$otherTable->id}")
            ->assertNotFound();
    }

    #[Test]
    public function show_returns_404_for_nonexistent_table(): void
    {
        $this->withHeaders($this->authHeaders())
            ->getJson('/api/price-tables/99999')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // PUT /api/price-tables/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function update_changes_table_name(): void
    {
        $table = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Antigo']);

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/price-tables/{$table->id}", ['name' => 'Novo Nome'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Novo Nome');

        $this->assertDatabaseHas('price_tables', ['id' => $table->id, 'name' => 'Novo Nome']);
    }

    #[Test]
    public function update_replaces_items(): void
    {
        $product1 = $this->createProduct();
        $product2 = $this->createProduct();
        $table    = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product1->id, 'price' => 10.00, 'min_quantity' => 1]);

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/price-tables/{$table->id}", [
                'items' => [['product_id' => $product2->id, 'price' => 99.00, 'min_quantity' => 1]],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('price_table_items', ['product_id' => $product1->id, 'price_table_id' => $table->id]);
        $this->assertDatabaseHas('price_table_items', ['product_id' => $product2->id, 'price' => 99.00]);
    }

    #[Test]
    public function update_returns_404_for_other_tenant(): void
    {
        $otherTable = PriceTable::factory()->create();

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/price-tables/{$otherTable->id}", ['name' => 'Hacked'])
            ->assertNotFound();
    }

    #[Test]
    public function update_fails_validation_with_invalid_type(): void
    {
        $table = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/price-tables/{$table->id}", ['type' => 'inexistente'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/price-tables/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function destroy_deletes_table_and_items(): void
    {
        $product = $this->createProduct();
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 10.00, 'min_quantity' => 1]);

        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/price-tables/{$table->id}")
            ->assertOk();

        $this->assertDatabaseMissing('price_tables', ['id' => $table->id]);
        $this->assertDatabaseMissing('price_table_items', ['price_table_id' => $table->id]);
    }

    #[Test]
    public function destroy_returns_404_for_other_tenant(): void
    {
        $otherTable = PriceTable::factory()->create();

        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/price-tables/{$otherTable->id}")
            ->assertNotFound();
    }

    #[Test]
    public function destroy_requires_authentication(): void
    {
        $table = PriceTable::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->deleteJson("/api/price-tables/{$table->id}")->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // GET /api/price-tables/lookup
    // -------------------------------------------------------------------------

    #[Test]
    public function lookup_returns_price_from_client_table(): void
    {
        $product = $this->createProduct(['price' => 100.00]);
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'padrao']);
        $client  = Client::factory()->create(['tenant_id' => $this->tenant->id, 'price_table_id' => $table->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 70.00, 'min_quantity' => 1]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/price-tables/lookup?product_id={$product->id}&client_id={$client->id}&quantity=1")
            ->assertOk();

        $this->assertEquals(70.0, (float) $response->json('data.price'));
    }

    #[Test]
    public function lookup_returns_null_when_no_table_found(): void
    {
        $product = $this->createProduct(['price' => 100.00]);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/price-tables/lookup?product_id={$product->id}&quantity=1")
            ->assertOk()
            ->assertJsonPath('data.price', null);
    }

    #[Test]
    public function lookup_fails_without_product_id(): void
    {
        $this->withHeaders($this->authHeaders())
            ->getJson('/api/price-tables/lookup?quantity=1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id']);
    }

    #[Test]
    public function lookup_applies_volume_tier_correctly(): void
    {
        $product = $this->createProduct();
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'padrao']);
        $client  = Client::factory()->create(['tenant_id' => $this->tenant->id, 'price_table_id' => $table->id]);

        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 20.00, 'min_quantity' => 1]);
        PriceTableItem::create(['price_table_id' => $table->id, 'product_id' => $product->id, 'price' => 12.00, 'min_quantity' => 10]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/price-tables/lookup?product_id={$product->id}&client_id={$client->id}&quantity=10")
            ->assertOk();

        $this->assertEquals(12.0, (float) $response->json('data.price'));
    }
}
