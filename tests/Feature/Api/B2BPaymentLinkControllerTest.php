<?php

namespace Tests\Feature\Api;

use App\Models\AccountReceivable;
use App\Models\Client;
use App\Models\Plan;
use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class B2BPaymentLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan          = Plan::factory()->create();
        $this->tenant  = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user    = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->token   = JWTAuth::fromUser($this->user);

        // Mock MercadoPagoService globally
        $this->app->bind(MercadoPagoService::class, function () {
            $mock = \Mockery::mock(MercadoPagoService::class);
            $mock->shouldReceive('createPaymentLink')->andReturn([
                'success'       => true,
                'preference_id' => 'pref-test-123',
                'init_point'    => 'https://mp.example.com/pay/123',
                'sandbox_url'   => 'https://sandbox.mp.example.com/pay/123',
            ]);
            return $mock;
        });
    }

    #[Test]
    public function generate_returns_422_for_order_in_invalid_status(): void
    {
        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'pendente',
            'total'     => 200.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/sale-orders/{$order->id}/payment-link");

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    #[Test]
    public function generate_returns_422_when_client_has_no_email(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id, 'email' => null]);
        $order  = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status'    => 'aprovado',
            'total'     => 200.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/sale-orders/{$order->id}/payment-link");

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    #[Test]
    public function generate_accepts_payer_email_in_request_body(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id, 'email' => null]);
        $order  = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status'    => 'aprovado',
            'total'     => 200.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/sale-orders/{$order->id}/payment-link", [
                'payer_email' => 'payer@test.com',
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['preference_id' => 'pref-test-123']);
    }

    #[Test]
    public function generate_creates_payment_link_for_approved_order(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'client@test.com']);
        $order  = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status'    => 'aprovado',
            'total'     => 500.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/sale-orders/{$order->id}/payment-link");

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['preference_id', 'payment_link', 'amount', 'description']]);
    }

    #[Test]
    public function generate_updates_accounts_receivable_with_mp_fields(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'client@test.com']);
        $order  = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status'    => 'faturado',
            'total'     => 300.00,
        ]);

        AccountReceivable::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'sale_order_id' => $order->id,
            'status'        => 'pendente',
            'amount'        => 300.00,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/sale-orders/{$order->id}/payment-link");

        $this->assertDatabaseHas('accounts_receivable', [
            'sale_order_id'   => $order->id,
            'mp_preference_id' => 'pref-test-123',
        ]);
    }

    #[Test]
    public function status_returns_404_when_no_payment_link_exists(): void
    {
        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'aprovado',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/sale-orders/{$order->id}/payment-link/status");

        $response->assertStatus(404);
    }

    #[Test]
    public function status_returns_payment_link_data_when_exists(): void
    {
        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'aprovado',
        ]);

        AccountReceivable::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'sale_order_id'   => $order->id,
            'mp_preference_id' => 'pref-abc',
            'mp_payment_link' => 'https://mp.example.com/pay/abc',
            'status'          => 'pendente',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/sale-orders/{$order->id}/payment-link/status");

        $response->assertStatus(200);
        $response->assertJsonFragment(['payment_link' => 'https://mp.example.com/pay/abc']);
    }
}
