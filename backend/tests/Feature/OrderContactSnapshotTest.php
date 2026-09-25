<?php

namespace Tests\Feature;

use App\Http\Resources\OrderResource;
use App\Models\Client;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderContactSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $attributes = []): Order
    {
        $tenant = Tenant::factory()->create();
        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Cadastro Antigo',
            'phone' => '71999999999',
            'email' => 'cadastro@example.com',
        ]);

        return Order::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
        ], $attributes))->load('client');
    }

    #[Test]
    public function resource_shows_the_contact_typed_for_the_order(): void
    {
        $order = $this->makeOrder([
            'customer_name' => 'Nome Digitado',
            'customer_phone' => '71988888888',
            'customer_email' => 'digitado@example.com',
        ]);

        $data = (new OrderResource($order))->resolve();

        $this->assertSame('Nome Digitado', $data['client_full_name']);
        $this->assertSame('71988888888', $data['client_phone']);
        $this->assertSame('digitado@example.com', $data['client_email']);
        // O cliente aninhado continua sendo o cadastro real
        $this->assertSame('Cadastro Antigo', $data['client']->resolve()['name']);
    }

    #[Test]
    public function resource_falls_back_to_the_customer_when_the_order_has_no_contact(): void
    {
        $data = (new OrderResource($this->makeOrder()))->resolve();

        $this->assertSame('Cadastro Antigo', $data['client_full_name']);
        $this->assertSame('71999999999', $data['client_phone']);
        $this->assertSame('cadastro@example.com', $data['client_email']);
    }
}
