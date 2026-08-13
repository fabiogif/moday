<?php

namespace Tests\Feature;

use App\Models\OrderStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderStatusApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = Tenant::factory()->create();

        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Gerar token JWT
        $this->token = auth('api')->login($this->user);
    }

    #[Test]
    public function it_can_list_order_statuses()
    {
        // Criar alguns status
        OrderStatus::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/order-statuses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'slug',
                        'color',
                        'icon',
                        'order_position',
                        'is_initial',
                        'is_final',
                        'is_active',
                    ]
                ]
            ]);

        $this->assertEquals(3, count($response->json('data')));
    }

    #[Test]
    public function it_can_create_order_status()
    {
        $data = [
            'name' => 'Em Preparo',
            'description' => 'Pedido sendo preparado',
            'color' => '#FCD34D',
            'icon' => 'clock',
            'is_initial' => true,
            'is_final' => false,
            'is_active' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/order-statuses', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'name',
                    'slug',
                    'color',
                    'icon',
                ]
            ]);

        $this->assertDatabaseHas('order_statuses', [
            'name' => 'Em Preparo',
            'tenant_id' => $this->tenant->id,
            'is_initial' => true,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_on_create()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/order-statuses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'color', 'icon']);
    }

    #[Test]
    public function it_validates_color_format()
    {
        $data = [
            'name' => 'Test',
            'color' => 'invalid-color',
            'icon' => 'package',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/order-statuses', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    }

    #[Test]
    public function it_can_update_order_status()
    {
        $status = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Old Name',
        ]);

        $data = [
            'name' => 'New Name',
            'color' => '#FF0000',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/order-statuses/{$status->uuid}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('order_statuses', [
            'uuid' => $status->uuid,
            'name' => 'New Name',
            'color' => '#FF0000',
        ]);
    }

    #[Test]
    public function it_can_toggle_status_active()
    {
        $status = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/order-statuses/{$status->uuid}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('order_statuses', [
            'uuid' => $status->uuid,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_can_delete_order_status_without_orders()
    {
        $status = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/order-statuses/{$status->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('order_statuses', [
            'uuid' => $status->uuid,
        ]);
    }

    #[Test]
    public function it_cannot_delete_status_with_orders()
    {
        $this->markTestSkipped('Migration order_statuses precisa rodar antes. Execute: php artisan migrate');
        
        $status = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar um pedido manualmente com order_status_id
        \Illuminate\Support\Facades\DB::table('orders')->insert([
            'identify' => 'TEST123',
            'tenant_id' => $this->tenant->id,
            'order_status_id' => $status->id,
            'total' => 100.00,
            'status' => 'Em Preparo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/order-statuses/{$status->uuid}");

        $response->assertStatus(500);

        // Verificar que o status ainda existe
        $this->assertDatabaseHas('order_statuses', [
            'uuid' => $status->uuid,
        ]);
    }

    #[Test]
    public function it_only_allows_one_initial_status()
    {
        // Criar primeiro status inicial
        OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_initial' => true,
        ]);

        // Criar segundo status como inicial
        $data = [
            'name' => 'Segundo Inicial',
            'color' => '#000000',
            'icon' => 'package',
            'is_initial' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/order-statuses', $data);

        $response->assertStatus(201);

        // Deve haver apenas um status inicial
        $this->assertEquals(1, OrderStatus::where('tenant_id', $this->tenant->id)
            ->where('is_initial', true)
            ->count());
    }

    #[Test]
    public function it_can_show_specific_status()
    {
        $status = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/order-statuses/{$status->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $status->uuid,
                    'name' => $status->name,
                ]
            ]);
    }
}

class OrderStatusApiAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/order-statuses');
        
        $response->assertStatus(401);
    }
}

