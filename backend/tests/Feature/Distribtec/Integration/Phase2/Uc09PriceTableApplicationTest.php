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
class Uc09PriceTableApplicationTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc09_applies_client_price_table_on_sale_order(): void
    {
        $product = $this->createProduct(['price' => 100.00]);
        $table   = PriceTable::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'padrao']);

        PriceTableItem::create([
            'price_table_id' => $table->id,
            'product_id'     => $product->id,
            'price'          => 75.00,
            'min_quantity'   => 1,
        ]);

        $client = Client::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'price_table_id'  => $table->id,
            'credit_limit'    => 10000,
        ]);

        $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'client_id' => $client->id,
            'status'    => 'orcamento',
            'items'     => [[
                'product_id' => $product->id,
                'quantity'   => 2,
            ]],
        ])->assertStatus(201)
            ->assertJsonPath('data.total', '150.00');
    }
}
