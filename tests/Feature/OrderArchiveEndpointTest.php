<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderArchiveEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $profile = Profile::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'profile_id' => $profile->id,
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_can_archive_order_successfully(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Em Preparo',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/order/{$order->identify}/archive");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pedido arquivado com sucesso',
            ]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_ARCHIVED, $order->status);
        $this->assertNotNull($order->archived_at);
    }

    public function test_cannot_archive_order_twice(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Order::STATUS_ARCHIVED,
            'archived_at' => now()->subDay(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/order/{$order->identify}/archive");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Pedido já está arquivado.',
            ]);
    }
}


