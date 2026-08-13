<?php

namespace Tests\Feature\Visit;

use App\Models\Client;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class VisitAclScopingTest extends TestCase
{
    private Tenant $tenant;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    /**
     * Concede só os slugs informados diretamente ao usuário (sem visits.view-all),
     * simulando um profile "Vendedor" restrito — diferente de
     * TestCase::grantFullAccess()/GrantsDistribtecPermissions, que dão acesso total.
     */
    private function grantOnly(User $user, array $slugs): void
    {
        $ids = [];
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug, 'tenant_id' => $this->tenant->id],
                ['name' => $slug, 'description' => $slug, 'module' => 'visits', 'action' => $slug, 'resource' => 'visits', 'is_active' => true]
            );
            $ids[] = $permission->id;
        }
        $user->permissions()->syncWithoutDetaching($ids);
    }

    private function auth(User $user): array
    {
        return ['Authorization' => 'Bearer ' . JWTAuth::fromUser($user)];
    }

    #[Test]
    public function seller_without_view_all_only_sees_own_visits(): void
    {
        $sellerA = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $sellerB = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantOnly($sellerA, ['visits.index', 'visits.store']);
        $this->grantOnly($sellerB, ['visits.index', 'visits.store']);

        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerA->id, 'client_id' => $this->client->id]);
        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerB->id, 'client_id' => $this->client->id]);

        $response = $this->withHeaders($this->auth($sellerA))->getJson('/api/visits');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($sellerA->id, $response->json('data.0.user.id'));
    }

    #[Test]
    public function supervisor_with_view_all_sees_every_seller_visits(): void
    {
        $sellerA = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $sellerB = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $supervisor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantOnly($supervisor, ['visits.index', 'visits.view-all']);

        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerA->id, 'client_id' => $this->client->id]);
        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerB->id, 'client_id' => $this->client->id]);

        $response = $this->withHeaders($this->auth($supervisor))->getJson('/api/visits');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function supervisor_can_filter_by_specific_seller(): void
    {
        $sellerA = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $sellerB = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $supervisor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantOnly($supervisor, ['visits.index', 'visits.view-all']);

        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerA->id, 'client_id' => $this->client->id]);
        Visit::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $sellerB->id, 'client_id' => $this->client->id]);

        $response = $this->withHeaders($this->auth($supervisor))->getJson('/api/visits?user_id=' . $sellerA->id);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($sellerA->id, $response->json('data.0.user.id'));
    }

    #[Test]
    public function seller_without_index_permission_is_forbidden(): void
    {
        $seller = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth($seller))->getJson('/api/visits')->assertStatus(403);
    }
}
