<?php

namespace Tests\Feature\Api;

use App\Models\BarcodeLookup;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductBarcodeLookupApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantFullAccess($this->user, $this->tenant);
        $this->token = JWTAuth::fromUser($this->user);

        config([
            'services.cosmos.token' => 'test-token',
            'services.cosmos.base_url' => 'https://api.cosmos.test',
            'services.open_food_facts.base_url' => 'https://world.openfoodfacts.test',
        ]);
    }

    private function authGet(string $uri)
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}")->getJson($uri);
    }

    #[Test]
    public function cti001_existing_product_flow(): void
    {
        Http::fake();

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'barcode' => '7891000100103',
            'name' => 'Já Cadastrado',
        ]);

        $this->authGet('/api/product/barcode-lookup/7891000100103')
            ->assertOk()
            ->assertJsonPath('data.status', 'existing_product')
            ->assertJsonPath('data.product.name', 'Já Cadastrado');

        Http::assertNothingSent();
    }

    #[Test]
    public function cti002_cosmos_flow_persists_cache(): void
    {
        Http::fake([
            'https://api.cosmos.test/gtins/*' => Http::response([
                'description' => 'Do Cosmos',
                'brand' => ['name' => 'Marca'],
            ], 200),
        ]);

        $this->authGet('/api/product/barcode-lookup/7891000100103')
            ->assertOk()
            ->assertJsonPath('data.status', 'found')
            ->assertJsonPath('data.source', 'cosmos')
            ->assertJsonPath('data.suggestion.name', 'Do Cosmos');

        $this->assertDatabaseHas('barcode_lookups', ['barcode' => '7891000100103']);
    }

    #[Test]
    public function cti003_open_food_facts_fallback_flow(): void
    {
        Http::fake([
            'https://api.cosmos.test/gtins/*' => Http::response([], 404),
            'https://world.openfoodfacts.test/api/v2/product/*' => Http::response([
                'status' => 1,
                'product' => ['product_name' => 'OFF Product', 'brands' => 'Brand'],
            ], 200),
        ]);

        $this->authGet('/api/product/barcode-lookup/7891000100103')
            ->assertOk()
            ->assertJsonPath('data.status', 'found')
            ->assertJsonPath('data.source', 'open_food_facts');
    }

    #[Test]
    public function cti004_apis_unavailable_allows_manual_registration(): void
    {
        Http::fake([
            'https://api.cosmos.test/gtins/*' => Http::response('err', 500),
            'https://world.openfoodfacts.test/api/v2/product/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            },
        ]);

        $this->authGet('/api/product/barcode-lookup/7891000100103')
            ->assertOk()
            ->assertJsonPath('data.status', 'not_found')
            ->assertJsonPath('data.message', 'Produto não encontrado nas bases externas.');
    }

    #[Test]
    public function cti007_duplicate_barcode_on_create(): void
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'barcode' => '7891000100103',
            'name' => 'Original',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->post('/api/product', [
                'name' => 'Duplicado Barcode',
                'description' => 'Descrição válida do produto',
                'price' => '10.00',
                'qtd_stock' => 1,
                'barcode' => '7891000100103',
            ]);

        $response->assertStatus(422);
        $this->assertTrue(
            $response->json('errors.barcode') !== null
            || str_contains(json_encode($response->json()), 'código de barras')
            || str_contains(json_encode($response->json()), 'barcode')
        );
    }

    #[Test]
    public function invalid_barcode_returns_422(): void
    {
        $this->authGet('/api/product/barcode-lookup/ABCD')
            ->assertStatus(422)
            ->assertJsonPath('data.status', 'invalid');
    }

    #[Test]
    public function cache_hit_skips_external_apis(): void
    {
        BarcodeLookup::create([
            'barcode' => '7891000100103',
            'source' => 'cosmos',
            'name' => 'Cached',
        ]);

        Http::fake();

        $this->authGet('/api/product/barcode-lookup/7891000100103')
            ->assertOk()
            ->assertJsonPath('data.source', 'cache');

        Http::assertNothingSent();
    }
}
