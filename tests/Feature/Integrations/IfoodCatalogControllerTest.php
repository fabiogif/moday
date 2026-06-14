<?php

namespace Tests\Feature\Integrations;

use App\Models\Integrations\Ifood\IfoodCatalogSnapshot;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Integrations\Ifood\IfoodTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class IfoodCatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_it_lists_catalogs(): void
    {
        $tenant = Tenant::factory()->create(['id' => 1]);
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        $tokenService = Mockery::mock(IfoodTokenService::class);
        $tokenService->shouldReceive('getValidAccessToken')
            ->once()
            ->with(1)
            ->andReturn('test-token');

        $this->app->instance(IfoodTokenService::class, $tokenService);

        Http::fake([
            'https://merchant-api.ifood.com.br/*' => Http::response([
                [
                    'catalogId' => 'catalog-123',
                    'context' => ['DEFAULT'],
                    'status' => 'AVAILABLE',
                    'modifiedAt' => 1597350642.71608,
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/integrations/ifood/catalogs?merchant_id=merchant-001&tenant_id=1');

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Catálogos iFood recuperados com sucesso.',
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_it_lists_categories_with_items(): void
    {
        $tenant = Tenant::factory()->create(['id' => 5]);
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        $tokenService = Mockery::mock(IfoodTokenService::class);
        $tokenService->shouldReceive('getValidAccessToken')
            ->once()
            ->with(5)
            ->andReturn('token-123');

        $this->app->instance(IfoodTokenService::class, $tokenService);

        Http::fake([
            'https://merchant-api.ifood.com.br/*' => Http::response([
                [
                    'id' => 'category-1',
                    'name' => 'Lanches',
                    'status' => 'AVAILABLE',
                    'items' => [
                        [
                            'id' => 'item-1',
                            'name' => 'X-Burguer',
                            'price' => [
                                'value' => 1000,
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/integrations/ifood/catalogs/catalog-1/categories?merchant_id=merchant-001&include_items=1&tenant_id=5');

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Categorias do catálogo iFood recuperadas com sucesso.',
            ])
            ->assertJsonFragment([
                'id' => 'category-1',
                'name' => 'Lanches',
            ])
            ->assertJsonFragment([
                'id' => 'item-1',
                'name' => 'X-Burguer',
            ]);
    }

    public function test_it_requires_merchant_id(): void
    {
        $tenant = Tenant::factory()->create(['id' => 2]);
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/integrations/ifood/catalogs?tenant_id=2');

        $response->assertStatus(422);
    }

    public function test_it_lists_unsellable_items(): void
    {
        $tenant = Tenant::factory()->create(['id' => 10]);
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        $tokenService = Mockery::mock(IfoodTokenService::class);
        $tokenService->shouldReceive('getValidAccessToken')
            ->once()
            ->with(10)
            ->andReturn('token-unsellable');

        $this->app->instance(IfoodTokenService::class, $tokenService);

        Http::fake([
            'https://merchant-api.ifood.com.br/catalog/v2.0/merchants/*/catalogs/*/unsellableItems' => Http::response([
                [
                    'id' => 'item-1',
                    'name' => 'Hambúrguer',
                    'sellable' => false,
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/integrations/ifood/catalogs/catalog-123/unsellable-items?merchant_id=merchant-abc&tenant_id=10');

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Itens indisponíveis do catálogo iFood recuperados com sucesso.',
            ])
            ->assertJsonFragment([
                'id' => 'item-1',
                'sellable' => false,
            ]);
    }

    public function test_it_lists_groups(): void
    {
        $tenant = Tenant::factory()->create(['id' => 9]);
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        $tokenService = Mockery::mock(IfoodTokenService::class);
        $tokenService->shouldReceive('getValidAccessToken')
            ->once()
            ->with(9)
            ->andReturn('token-groups');

        $this->app->instance(IfoodTokenService::class, $tokenService);

        Http::fake([
            'https://merchant-api.ifood.com.br/catalog/v2.0/merchants/*/catalogs/*/categories' => Http::response([
                [
                    'id' => 'group-1',
                    'name' => 'Lanches',
                    'status' => 'AVAILABLE',
                    'items' => [
                        ['id' => 'item-1'],
                    ],
                ],
                [
                    'id' => 'group-2',
                    'name' => 'Bebidas',
                    'status' => 'AVAILABLE',
                    'items' => [],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/integrations/ifood/catalogs/catalog-abc/groups?merchant_id=merchant-xyz&tenant_id=9');

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Grupos do catálogo iFood recuperados com sucesso.',
            ])
            ->assertJsonFragment([
                'id' => 'group-1',
                'name' => 'Lanches',
            ]);

        $this->assertDatabaseHas('ifood_catalog_snapshots', [
            'tenant_id' => 9,
            'merchant_id' => 'merchant-xyz',
            'catalog_id' => 'catalog-abc',
            'snapshot_type' => 'catalog_groups',
        ]);
    }

    public function test_it_lists_sellable_items_by_group(): void
    {
        $tenant = Tenant::factory()->create(['id' => 11]);
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        $tokenService = Mockery::mock(IfoodTokenService::class);
        $tokenService->shouldReceive('getValidAccessToken')
            ->once()
            ->with(11)
            ->andReturn('token-sellable');

        $this->app->instance(IfoodTokenService::class, $tokenService);

        Http::fake([
            'https://merchant-api.ifood.com.br/catalog/v2.0/merchants/*/catalogs/*/sellableItems' => Http::response([
                [
                    'id' => 'item-2',
                    'name' => 'Refrigerante',
                    'sellable' => true,
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/integrations/ifood/catalog-groups/group-456/sellable-items?merchant_id=merchant-abc&tenant_id=11');

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Itens vendáveis do catálogo iFood recuperados com sucesso.',
            ])
            ->assertJsonFragment([
                'id' => 'item-2',
                'sellable' => true,
            ]);
    }

    public function test_it_retrieves_catalog_version(): void
    {
        $tenant = Tenant::factory()->create(['id' => 12]);
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        $tokenService = Mockery::mock(IfoodTokenService::class);
        $tokenService->shouldReceive('getValidAccessToken')
            ->once()
            ->with(12)
            ->andReturn('token-version');

        $this->app->instance(IfoodTokenService::class, $tokenService);

        Http::fake([
            'https://merchant-api.ifood.com.br/catalog/v2.0/merchants/*/catalog/version' => Http::response([
                'version' => 'v2',
                'lastUpdate' => '2025-01-01T12:00:00Z',
            ], 200),
        ]);

        $response = $this->getJson('/api/integrations/ifood/catalog/version?merchant_id=merchant-abc&tenant_id=12');

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Versão do catálogo iFood recuperada com sucesso.',
            ])
            ->assertJsonFragment([
                'version' => 'v2',
            ]);

        $this->assertDatabaseHas('ifood_catalog_snapshots', [
            'tenant_id' => 12,
            'merchant_id' => 'merchant-abc',
            'snapshot_type' => 'catalog_version',
        ]);
    }

    public function test_it_paginate_snapshots(): void
    {
        $tenant = Tenant::factory()->create(['id' => 13]);
        $profile = Profile::factory()->create();

        /** @var User $user */
        $user = User::factory()
            ->for($tenant)
            ->for($profile)
            ->create();

        $this->actingAs($user, 'api');

        IfoodCatalogSnapshot::factory()->count(3)->create([
            'tenant_id' => 13,
            'merchant_id' => 'merchant-xyz',
            'snapshot_type' => 'catalogs',
        ]);

        $response = $this->getJson('/api/integrations/ifood/catalog/snapshots?merchant_id=merchant-xyz&tenant_id=13&per_page=2');

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
            ]);

        $payload = $response->json();

        $this->assertEquals(2, $payload['meta']['per_page'] ?? null);
        $this->assertNotEmpty($payload['data'] ?? []);
    }
}

