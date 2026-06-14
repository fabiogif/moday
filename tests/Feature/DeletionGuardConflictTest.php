<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeletionGuardConflictTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
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

    public function test_client_deletion_is_blocked_when_active_orders_exist(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'Em Preparo',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/client/{$client->id}");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Cliente não pode ser excluído, existe um pedido ativo ou não arquivado vinculado.',
            ]);
    }

    public function test_client_deletion_is_allowed_when_orders_are_archived(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => Order::STATUS_ARCHIVED,
            'archived_at' => now()->subDay(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/client/{$client->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_product_deletion_is_blocked_when_active_orders_exist(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Em Preparo',
        ]);
        $order->products()->attach($product->id, ['qty' => 1, 'price' => $product->price]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/product/{$product->id}");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Produto não pode ser excluído, existe um pedido ativo ou não arquivado vinculado.',
            ]);
    }

    public function test_product_deletion_is_allowed_after_archiving_orders(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Order::STATUS_ARCHIVED,
            'archived_at' => now()->subDay(),
        ]);
        $order->products()->attach($product->id, ['qty' => 1, 'price' => $product->price]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/product/{$product->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_table_deletion_is_blocked_when_active_orders_exist(): void
    {
        $table = Table::factory()->create(['tenant_id' => $this->tenant->id]);

        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $table->id,
            'status' => 'Em Preparo',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/table/{$table->uuid}");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Mesa não pode ser excluída, existe um pedido ativo ou não arquivado vinculado.',
            ]);
    }

    public function test_table_deletion_is_allowed_after_archiving_orders(): void
    {
        $table = Table::factory()->create(['tenant_id' => $this->tenant->id]);

        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $table->id,
            'status' => Order::STATUS_ARCHIVED,
            'archived_at' => now()->subDay(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/table/{$table->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'is_active' => 0,
        ]);
    }

    public function test_payment_method_deletion_is_blocked_when_active_orders_exist(): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['tenant_id' => $this->tenant->id]);

        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'payment_method_id' => $paymentMethod->uuid,
            'status' => 'Em Preparo',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/payment-methods/{$paymentMethod->uuid}");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Forma de pagamento não pode ser excluída, existe um pedido ativo ou não arquivado vinculado.',
            ]);
    }

    public function test_payment_method_deletion_is_allowed_after_orders_archived(): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['tenant_id' => $this->tenant->id]);

        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'payment_method_id' => $paymentMethod->uuid,
            'status' => Order::STATUS_ARCHIVED,
            'archived_at' => now()->subDay(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/payment-methods/{$paymentMethod->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('payment_methods', ['id' => $paymentMethod->id]);
    }

    public function test_deletion_is_allowed_when_no_orders_exist(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/client/{$client->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }
}


