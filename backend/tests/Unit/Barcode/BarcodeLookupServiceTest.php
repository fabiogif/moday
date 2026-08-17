<?php

namespace Tests\Unit\Barcode;

use App\Models\BarcodeLookup;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Services\Barcode\BarcodeLookupService;
use App\Services\Barcode\BarcodeValidator;
use App\Services\Barcode\Clients\CosmosBarcodeClient;
use App\Services\Barcode\Clients\OpenFoodFactsBarcodeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BarcodeLookupServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private BarcodeLookupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        User::factory()->create(['tenant_id' => $this->tenant->id]);

        config([
            'services.cosmos.token' => 'test-token',
            'services.cosmos.base_url' => 'https://api.cosmos.test',
            'services.cosmos.timeout' => 2,
            'services.open_food_facts.base_url' => 'https://world.openfoodfacts.test',
            'services.open_food_facts.timeout' => 3,
        ]);

        $this->service = new BarcodeLookupService(
            new BarcodeValidator(),
            new ProductRepository(new Product()),
            new CosmosBarcodeClient(),
            new OpenFoodFactsBarcodeClient(),
        );
    }

    #[Test]
    public function ctu001_returns_local_product_without_external_calls(): void
    {
        Http::fake();

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'barcode' => '7891000100103',
            'name' => 'Leite Local',
        ]);

        $result = $this->service->lookup('7891000100103', $this->tenant->id);

        $this->assertSame(BarcodeLookupService::STATUS_EXISTING, $result['status']);
        $this->assertSame('Leite Local', $result['product']['name']);
        Http::assertNothingSent();
    }

    #[Test]
    public function ctu002_and_ctu004_queries_cosmos_and_caches(): void
    {
        Http::fake([
            'https://api.cosmos.test/gtins/*' => Http::response([
                'description' => 'Produto Cosmos',
                'brand' => ['name' => 'Marca X'],
                'thumbnail' => 'https://img.test/p.jpg',
                'net_weight' => 1.5,
            ], 200),
        ]);

        $result = $this->service->lookup('7891000100103', $this->tenant->id);

        $this->assertSame(BarcodeLookupService::STATUS_FOUND, $result['status']);
        $this->assertSame('cosmos', $result['source']);
        $this->assertSame('Produto Cosmos', $result['suggestion']['name']);
        $this->assertDatabaseHas('barcode_lookups', [
            'barcode' => '7891000100103',
            'source' => 'cosmos',
            'name' => 'Produto Cosmos',
        ]);

        Http::assertSentCount(1);
    }

    #[Test]
    public function ctu003_and_ctu005_falls_back_to_open_food_facts(): void
    {
        Http::fake([
            'https://api.cosmos.test/gtins/*' => Http::response([], 404),
            'https://world.openfoodfacts.test/api/v2/product/*' => Http::response([
                'status' => 1,
                'product' => [
                    'product_name' => 'Produto OFF',
                    'brands' => 'Marca OFF',
                    'quantity' => '500 g',
                    'image_front_url' => 'https://img.test/off.jpg',
                    'categories_tags' => ['en:beverages'],
                ],
            ], 200),
        ]);

        $result = $this->service->lookup('7891000100103', $this->tenant->id);

        $this->assertSame(BarcodeLookupService::STATUS_FOUND, $result['status']);
        $this->assertSame('open_food_facts', $result['source']);
        $this->assertSame('Produto OFF', $result['suggestion']['name']);
        $this->assertSame(0.5, $result['suggestion']['weight']);
        $this->assertDatabaseHas('barcode_lookups', ['barcode' => '7891000100103', 'source' => 'open_food_facts']);
    }

    #[Test]
    public function ctu006_apis_down_returns_not_found_without_exception(): void
    {
        Http::fake([
            'https://api.cosmos.test/gtins/*' => Http::response('error', 500),
            'https://world.openfoodfacts.test/api/v2/product/*' => Http::response('error', 503),
        ]);

        $result = $this->service->lookup('7891000100103', $this->tenant->id);

        $this->assertSame(BarcodeLookupService::STATUS_NOT_FOUND, $result['status']);
        $this->assertSame('Produto não encontrado nas bases externas.', $result['message']);
    }

    #[Test]
    #[DataProvider('invalidCodesProvider')]
    public function ctu007_rejects_invalid_codes(?string $code): void
    {
        Http::fake();

        $result = $this->service->lookup($code, $this->tenant->id);

        $this->assertSame(BarcodeLookupService::STATUS_INVALID, $result['status']);
        Http::assertNothingSent();
    }

    public static function invalidCodesProvider(): array
    {
        return [
            'empty' => [''],
            'null' => [null],
            'letters' => ['ABC12345'],
            'special' => ['7891-000'],
            'too_short' => ['1234567'],
            'too_long' => ['12345678901234'],
        ];
    }

    #[Test]
    public function ctu009_maps_fields_from_open_food_facts(): void
    {
        Http::fake([
            'https://api.cosmos.test/gtins/*' => Http::response([], 404),
            'https://world.openfoodfacts.test/api/v2/product/*' => Http::response([
                'status' => 1,
                'product' => [
                    'product_name_pt' => 'Refrigerante 2L',
                    'brands' => 'Coca-Cola, Outra',
                    'quantity' => '2 l',
                    'image_url' => 'https://img.test/refri.jpg',
                    'categories' => 'Bebidas, Refrigerantes',
                ],
            ], 200),
        ]);

        $result = $this->service->lookup('7891000100103', $this->tenant->id);
        $s = $result['suggestion'];

        $this->assertSame('Refrigerante 2L', $s['name']);
        $this->assertSame('Coca-Cola', $s['brand']);
        $this->assertSame('Bebidas', $s['category']);
        $this->assertSame(2.0, $s['volume']);
        $this->assertSame('https://img.test/refri.jpg', $s['image_url']);
    }

    #[Test]
    public function ctu010_uses_cache_and_skips_apis(): void
    {
        BarcodeLookup::create([
            'barcode' => '7891000100103',
            'source' => 'cosmos',
            'name' => 'Cache Hit',
            'brand' => 'Marca',
        ]);

        Http::fake();

        $result = $this->service->lookup('7891000100103', $this->tenant->id);

        $this->assertSame(BarcodeLookupService::STATUS_FOUND, $result['status']);
        $this->assertSame('cache', $result['source']);
        $this->assertSame('Cache Hit', $result['suggestion']['name']);
        Http::assertNothingSent();
    }
}
