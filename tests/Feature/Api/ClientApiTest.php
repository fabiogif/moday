<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Plan;
use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientApiTest extends TestCase
{
    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    private function validB2bPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Farmácia Central LTDA',
            'trade_name' => 'Central Farma',
            'cnpj' => '11.222.333/0001-81',
            'client_type' => 'farmacia',
            'credit_limit' => 5000,
            'payment_term_days' => 30,
            'email' => 'compras@centralfarma.test',
            'phone' => '11987654321',
            'city' => 'São Paulo',
            'state' => 'SP',
            'is_active' => true,
        ], $overrides);
    }

    #[Test]
    public function guest_cannot_list_clients(): void
    {
        $this->getJson('/api/clients')->assertStatus(401);
    }

    #[Test]
    public function it_sorts_clients_by_most_recent_order_when_requested(): void
    {
        $stale = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $recent = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $noOrder = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $stale->id,
            'ordered_at' => Carbon::now()->subDays(10),
        ]);
        SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $recent->id,
            'ordered_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->withHeaders($this->auth())
            ->getJson('/api/clients?sort=recent_orders&per_page=10')
            ->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->values();

        $this->assertSame($recent->id, $ids[0]);
        $this->assertSame($stale->id, $ids[1]);
        $this->assertSame($noOrder->id, $ids[2]);
    }

    #[Test]
    public function it_lists_only_tenant_clients(): void
    {
        Client::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => Plan::factory()->create()->id]);
        Client::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeaders($this->auth())
            ->getJson('/api/clients')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_caps_listing_when_no_pagination_is_requested(): void
    {
        config()->set('api.listing.unpaginated_cap', 3);

        Client::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->getJson('/api/clients')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.per_page', 3);
    }

    #[Test]
    public function it_caps_per_page_above_the_configured_maximum(): void
    {
        config()->set('api.listing.max_per_page', 2);

        Client::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->getJson('/api/clients?page=1&per_page=100')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    #[Test]
    public function it_creates_b2b_client(): void
    {
        $payload = $this->validB2bPayload();

        $response = $this->withHeaders($this->auth())
            ->postJson('/api/clients', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.company_name', 'Farmácia Central LTDA')
            ->assertJsonPath('data.client_type', 'farmacia');

        $this->assertDatabaseHas('clients', [
            'tenant_id' => $this->tenant->id,
            'company_name' => 'Farmácia Central LTDA',
            'client_type' => 'farmacia',
        ]);
    }

    #[Test]
    public function resending_the_same_client_request_id_does_not_duplicate_the_client(): void
    {
        $payload = $this->validB2bPayload(['client_request_id' => 'mobile-client-uuid-1']);

        $first = $this->withHeaders($this->auth())->postJson('/api/clients', $payload);
        $first->assertStatus(201);

        $second = $this->withHeaders($this->auth())->postJson('/api/clients', $payload);
        $second->assertStatus(201);

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(
            1,
            Client::where('tenant_id', $this->tenant->id)
                ->where('client_request_id', 'mobile-client-uuid-1')
                ->count()
        );
    }

    #[Test]
    public function the_same_client_request_id_in_different_tenants_creates_two_distinct_clients(): void
    {
        // Exercised at the service layer for the same reason as the sale-order equivalent
        // (see SaleOrderIdempotencyTest): this test harness's JWT guard resolves the first
        // authenticated user for any subsequent request in the same test method, regardless
        // of the bearer token sent. The dedup logic under test lives entirely in
        // ClientService::findOrCreateByRequestId(), so calling it directly per tenant is
        // an equally valid way to verify the unique index is scoped by tenant_id.
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => Plan::factory()->create()->id]);

        $service = app(\App\Services\ClientService::class);
        $data    = $this->validB2bPayload(['client_request_id' => 'shared-client-uuid', 'name' => 'Farmácia Central LTDA']);

        $result1 = $service->findOrCreateByRequestId(array_merge($data, ['tenant_id' => $this->tenant->id]), $this->tenant->id);
        $result2 = $service->findOrCreateByRequestId(array_merge($data, ['tenant_id' => $otherTenant->id]), $otherTenant->id);

        $this->assertNotSame($result1['client']->id, $result2['client']->id);
        $this->assertSame(
            2,
            Client::where('client_request_id', 'shared-client-uuid')->count()
        );
    }

    #[Test]
    public function it_shows_client_by_id(): void
    {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_name' => 'Hospital Regional',
        ]);

        $this->withHeaders($this->auth())
            ->getJson("/api/clients/{$client->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $client->id);
    }

    #[Test]
    public function it_updates_client(): void
    {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_name' => 'Nome Original',
            'credit_limit' => 1000,
        ]);

        $this->withHeaders($this->auth())
            ->putJson("/api/clients/{$client->id}", [
                'company_name' => 'Nome Atualizado',
                'credit_limit' => 7500,
                'payment_term_days' => 45,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.company_name', 'Nome Atualizado');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'company_name' => 'Nome Atualizado',
            'credit_limit' => 7500,
        ]);
    }

    #[Test]
    public function it_deletes_client(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->deleteJson("/api/clients/{$client->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    #[Test]
    public function it_cannot_access_client_from_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => Plan::factory()->create()->id]);
        $client = Client::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeaders($this->auth())
            ->getJson("/api/clients/{$client->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function it_rejects_invalid_cnpj_on_create(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/api/clients', $this->validB2bPayload(['cnpj' => '11.111.111/1111-11']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cnpj']);
    }

    #[Test]
    public function it_includes_last_order_date_from_sale_orders_in_list(): void
    {
        $withOrder = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $withoutOrder = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $withOrder->id,
            'ordered_at' => Carbon::parse('2026-07-10 14:30:00'),
        ]);

        app(\App\Services\CacheService::class)->invalidateClientCache($this->tenant->id);

        $response = $this->withHeaders($this->auth())
            ->getJson('/api/clients')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $byId = collect($response->json('data'))->keyBy('id');

        $this->assertSame('10/07/2026', $byId[$withOrder->id]['last_order']);
        $this->assertNull($byId[$withoutOrder->id]['last_order']);
        $this->assertSame(1, $byId[$withOrder->id]['total_orders']);
        $this->assertSame(0, $byId[$withoutOrder->id]['total_orders']);
    }

    #[Test]
    public function it_paginates_clients_when_page_params_are_present(): void
    {
        Client::factory()->count(15)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->auth())
            ->getJson('/api/clients?page=1&per_page=10')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(10, 'data');

        $this->assertSame(1, $response->json('meta.current_page'));
        $this->assertSame(2, $response->json('meta.last_page'));
        $this->assertSame(15, $response->json('meta.total'));

        $this->withHeaders($this->auth())
            ->getJson('/api/clients?page=2&per_page=10')
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function it_filters_clients_by_search_when_paginated(): void
    {
        Client::factory()->create(['tenant_id' => $this->tenant->id, 'company_name' => 'Farmácia Central']);
        Client::factory()->create(['tenant_id' => $this->tenant->id, 'company_name' => 'Distribuidora Norte']);

        $this->withHeaders($this->auth())
            ->getJson('/api/clients?page=1&per_page=10&search=Central')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company_name', 'Farmácia Central');
    }

    #[Test]
    public function it_caps_per_page_at_the_configured_maximum_for_paginated_clients(): void
    {
        $this->withHeaders($this->auth())
            ->getJson('/api/clients?page=1&per_page=1000')
            ->assertStatus(200)
            ->assertJsonPath('meta.per_page', config('api.listing.max_per_page'));
    }
}
