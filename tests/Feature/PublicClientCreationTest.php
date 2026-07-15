<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Models\User;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use App\Repositories\Contracts\PublicStoreRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PublicClientCreationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Product $product;
    private PaymentMethod $paymentMethod;
    private string $slug;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('order_statuses')) {
            Schema::create('order_statuses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid');
                $table->unsignedBigInteger('tenant_id');
                $table->string('name', 100);
                $table->string('slug', 100);
                $table->text('description')->nullable();
                $table->string('color', 7)->default('#6B7280');
                $table->string('icon', 50)->default('package');
                $table->integer('order_position')->default(0);
                $table->boolean('is_initial')->default(false);
                $table->boolean('is_final')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        
        // Criar plano
        $plan = Plan::factory()->create([
            'name' => 'Plano Teste',
            'description' => 'Plano para testes',
            'price' => 99.99,
            'max_products' => 100,
            'max_orders_per_month' => 1000,
            'is_active' => true,
        ]);

        // Criar tenant
        $this->tenant = Tenant::factory()->accessible()->create([
            'uuid' => fake()->uuid(),
            'name' => 'Loja Teste',
            'slug' => 'loja-teste',
            'phone' => '71999999999',
            'email' => 'loja@teste.com',
            'cnpj' => '12345678000199',
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);
        
        $this->slug = $this->tenant->slug;

        // Criar produto
        $this->product = Product::create([
            'uuid' => fake()->uuid(),
            'name' => 'Produto Teste',
            'price' => 50.00,
            'qtd_stock' => 100,
            'is_active' => true,
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar método de pagamento
        $this->paymentMethod = PaymentMethod::create([
            'uuid' => fake()->uuid(),
            'name' => 'Dinheiro',
            'is_active' => true,
            'tenant_id' => $this->tenant->id,
        ]);

        DB::table('order_statuses')->insert([
            'uuid' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'name' => 'Novo Pedido',
            'slug' => 'novo-pedido',
            'description' => 'Pedido recebido',
            'color' => '#3B82F6',
            'icon' => 'loader',
            'order_position' => 1,
            'is_initial' => true,
            'is_final' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Mock do repository
        $this->mockPublicStoreRepository();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('order_statuses');
        parent::tearDown();
    }

    private function mockPublicStoreRepository()
    {
        $tenant = $this->tenant;
        $product = $this->product;
        $paymentMethod = $this->paymentMethod;
        
        app()->bind(PublicStoreRepositoryInterface::class, function () use ($tenant, $product, $paymentMethod) {
            return new class($tenant, $product, $paymentMethod) implements PublicStoreRepositoryInterface {
                private Tenant $tenant;
                private Product $product;
                private PaymentMethod $paymentMethod;

                public function __construct(Tenant $tenant, Product $product, PaymentMethod $paymentMethod)
                {
                    $this->tenant = $tenant;
                    $this->product = $product;
                    $this->paymentMethod = $paymentMethod;
                }

                public function getTenantBySlug(string $slug): ?Tenant
                {
                    return $this->tenant->slug === $slug ? $this->tenant : null;
                }

                public function getActiveProducts(int $tenantId): array
                {
                    return $tenantId === $this->tenant->id ? [$this->product] : [];
                }

                public function getActivePaymentMethods(int $tenantId): array
                {
                    return $tenantId === $this->tenant->id ? [$this->paymentMethod] : [];
                }
            };
        });
    }

    #[Test]
    public function it_creates_new_client_when_making_first_order()
    {
        $orderData = [
            'client' => [
                'name' => 'João Silva',
                'email' => 'joao@teste.com',
                'phone' => '71999999999',
                'cpf' => '111.111.111-11',
            ],
            'products' => [
                ['uuid' => $this->product->uuid, 'quantity' => 2]
            ],
            'delivery' => [
                'is_delivery' => false,
            ],
            'payment_method' => $this->paymentMethod->uuid,
            'shipping_method' => 'pickup',
        ];

        $response = $this->postJson("/api/store/{$this->slug}/orders", $orderData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['order_id', 'total']
            ]);

        // Verificar que cliente foi criado
        $this->assertDatabaseHas('clients', [
            'name' => 'João Silva',
            'email' => 'joao@teste.com',
            'cpf' => '11111111111', // CPF limpo
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        // Verificar que pedido está vinculado ao cliente
        $client = Client::where('email', 'joao@teste.com')->first();
        $this->assertNotNull($client);
        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'tenant_id' => $this->tenant->id,
            'origin' => 'store',
            'is_delivery' => false,
            'shipping_method' => 'pickup',
        ]);
    }

    #[Test]
    public function it_persists_delivery_type_and_address_for_delivery_orders(): void
    {
        $orderData = [
            'client' => [
                'name' => 'Ana Delivery',
                'email' => 'ana@teste.com',
                'phone' => '71977776666',
            ],
            'products' => [
                ['uuid' => $this->product->uuid, 'quantity' => 1],
            ],
            'delivery' => [
                'is_delivery' => true,
                'address' => 'Rua das Palmeiras',
                'number' => '456',
                'neighborhood' => 'Brotas',
                'city' => 'Salvador',
                'state' => 'BA',
                'zip_code' => '40285000',
                'complement' => 'Casa',
                'notes' => 'Interfone 101',
            ],
            'payment_method' => $this->paymentMethod->uuid,
            'shipping_method' => 'delivery',
        ];

        $response = $this->postJson("/api/store/{$this->slug}/orders", $orderData);

        $response->assertStatus(201);

        $client = Client::where('email', 'ana@teste.com')->first();
        $this->assertNotNull($client);

        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'tenant_id' => $this->tenant->id,
            'is_delivery' => true,
            'shipping_method' => 'delivery',
            'delivery_address' => 'Rua das Palmeiras',
            'delivery_number' => '456',
            'delivery_neighborhood' => 'Brotas',
            'delivery_city' => 'Salvador',
            'delivery_state' => 'BA',
            'delivery_zip_code' => '40285000',
        ]);

        $client->refresh();
        $this->assertEquals('Rua das Palmeiras', $client->address);
        $this->assertEquals('456', $client->number);
        $this->assertEquals('Brotas', $client->neighborhood);
    }

    #[Test]
    public function it_lookups_client_by_cpf_and_returns_saved_address(): void
    {
        Client::create([
            'uuid' => fake()->uuid(),
            'name' => 'Cliente Cadastrado',
            'email' => 'cadastrado@teste.com',
            'phone' => '71988887777',
            'cpf' => '12345678901',
            'address' => 'Av. Principal',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'Salvador',
            'state' => 'BA',
            'zip_code' => '40000000',
            'complement' => 'Bloco A',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/store/{$this->slug}/clients/lookup?cpf=12345678901");

        $response->assertStatus(200)
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.client.name', 'Cliente Cadastrado')
            ->assertJsonPath('data.address.address', 'Av. Principal')
            ->assertJsonPath('data.address.number', '100')
            ->assertJsonPath('data.address.city', 'Salvador');
    }

    #[Test]
    public function it_lookups_client_by_phone_and_returns_address_from_last_delivery_order(): void
    {
        $client = Client::create([
            'uuid' => fake()->uuid(),
            'name' => 'Cliente Telefone',
            'email' => 'telefone@teste.com',
            'phone' => '71977776666',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        \App\Models\Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'is_delivery' => true,
            'delivery_address' => 'Rua do Pedido',
            'delivery_number' => '50',
            'delivery_neighborhood' => 'Pituba',
            'delivery_city' => 'Salvador',
            'delivery_state' => 'BA',
            'delivery_zip_code' => '41810000',
            'delivery_complement' => 'Casa 2',
        ]);

        $response = $this->getJson("/api/store/{$this->slug}/clients/lookup?phone=71977776666");

        $response->assertStatus(200)
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.client.name', 'Cliente Telefone')
            ->assertJsonPath('data.address.address', 'Rua do Pedido')
            ->assertJsonPath('data.address.neighborhood', 'Pituba');
    }

    #[Test]
    public function it_updates_existing_client_when_cpf_already_exists()
    {
        // Criar cliente existente
        $existingClient = Client::create([
            'uuid' => fake()->uuid(),
            'name' => 'João',
            'email' => 'joao@teste.com',
            'phone' => '71999999999',
            'cpf' => '11111111111',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $orderData = [
            'client' => [
                'name' => 'João Silva Santos', // Nome diferente
                'email' => 'joao@teste.com',
                'phone' => '71988888888', // Telefone diferente
                'cpf' => '111.111.111-11', // Mesmo CPF (com formatação)
            ],
            'products' => [
                ['uuid' => $this->product->uuid, 'quantity' => 1]
            ],
            'delivery' => [
                'is_delivery' => false,
            ],
            'payment_method' => $this->paymentMethod->uuid,
            'shipping_method' => 'pickup',
        ];

        $response = $this->postJson("/api/store/{$this->slug}/orders", $orderData);

        $response->assertStatus(201);

        // Verificar que não criou cliente duplicado
        $this->assertEquals(1, Client::where('cpf', '11111111111')->count());

        // Verificar que atualizou os dados
        $existingClient->refresh();
        $this->assertEquals('João Silva Santos', $existingClient->name);
        $this->assertEquals('71988888888', $existingClient->phone);
        $this->assertEquals('joao@teste.com', $existingClient->email);
    }

    #[Test]
    public function it_keeps_current_email_when_new_email_already_exists()
    {
        // Cliente A
        $clientA = Client::create([
            'uuid' => fake()->uuid(),
            'name' => 'Cliente A',
            'email' => 'clienteA@teste.com',
            'phone' => '71999999999',
            'cpf' => '11111111111',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        // Cliente B (com email diferente)
        Client::create([
            'uuid' => fake()->uuid(),
            'name' => 'Cliente B',
            'email' => 'clienteB@teste.com',
            'phone' => '71988888888',
            'cpf' => '22222222222',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        // Cliente A tenta fazer pedido com email do Cliente B
        $orderData = [
            'client' => [
                'name' => 'Cliente A Atualizado',
                'email' => 'clienteB@teste.com', // Email já existe
                'phone' => '71977777777',
                'cpf' => '111.111.111-11', // CPF do Cliente A
            ],
            'products' => [
                ['uuid' => $this->product->uuid, 'quantity' => 1]
            ],
            'delivery' => [
                'is_delivery' => false,
            ],
            'payment_method' => $this->paymentMethod->uuid,
            'shipping_method' => 'pickup',
        ];

        $response = $this->postJson("/api/store/{$this->slug}/orders", $orderData);

        $response->assertStatus(201);

        // Verificar que atualizou nome e telefone, mas MANTEVE o email original
        $clientA->refresh();
        $this->assertEquals('Cliente A Atualizado', $clientA->name);
        $this->assertEquals('71977777777', $clientA->phone);
        $this->assertEquals('clienteA@teste.com', $clientA->email); // Email mantido
    }

    #[Test]
    public function it_finds_client_by_email_when_no_cpf_provided()
    {
        // Cliente existente sem CPF
        $existingClient = Client::create([
            'uuid' => fake()->uuid(),
            'name' => 'Maria',
            'email' => 'maria@teste.com',
            'phone' => '71999999999',
            'cpf' => null,
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $orderData = [
            'client' => [
                'name' => 'Maria Santos', // Nome diferente
                'email' => 'maria@teste.com', // Mesmo email
                'phone' => '71988888888',
            ],
            'products' => [
                ['uuid' => $this->product->uuid, 'quantity' => 1]
            ],
            'delivery' => [
                'is_delivery' => false,
            ],
            'payment_method' => $this->paymentMethod->uuid,
            'shipping_method' => 'pickup',
        ];

        $response = $this->postJson("/api/store/{$this->slug}/orders", $orderData);

        $response->assertStatus(201);

        // Verificar que não duplicou
        $this->assertEquals(1, Client::where('email', 'maria@teste.com')->count());

        // Verificar que atualizou
        $existingClient->refresh();
        $this->assertEquals('Maria Santos', $existingClient->name);
    }

    #[Test]
    public function it_sanitizes_phone_and_cpf_before_saving()
    {
        $orderData = [
            'client' => [
                'name' => 'Pedro',
                'email' => 'pedro@teste.com',
                'phone' => '71999999999',
                'cpf' => '123.456.789-01', // Com formatação
            ],
            'products' => [
                ['uuid' => $this->product->uuid, 'quantity' => 1]
            ],
            'delivery' => [
                'is_delivery' => false,
            ],
            'payment_method' => $this->paymentMethod->uuid,
            'shipping_method' => 'pickup',
        ];

        $response = $this->postJson("/api/store/{$this->slug}/orders", $orderData);

        $response->assertStatus(201);

        // Verificar que CPF foi salvo sem formatação
        $this->assertDatabaseHas('clients', [
            'email' => 'pedro@teste.com',
            'cpf' => '12345678901', // Sem pontos e traços
        ]);
    }

    #[Test]
    public function created_client_appears_in_admin_list()
    {
        // Criar cliente via loja pública
        $orderData = [
            'client' => [
                'name' => 'Carlos',
                'email' => 'carlos@teste.com',
                'phone' => '71999999999',
                'cpf' => '444.444.444-44',
            ],
            'products' => [
                ['uuid' => $this->product->uuid, 'quantity' => 1]
            ],
            'delivery' => [
                'is_delivery' => false,
            ],
            'payment_method' => $this->paymentMethod->uuid,
            'shipping_method' => 'pickup',
        ];

        $this->postJson("/api/store/{$this->slug}/orders", $orderData)
            ->assertStatus(201);

        // Buscar na API do admin (precisa de autenticação)
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/client');

        $response->assertStatus(200);
        
        // Verificar que o cliente está na lista
        $clients = $response->json('data');
        $this->assertNotEmpty($clients);
        
        $carlos = collect($clients)->firstWhere('email', 'carlos@teste.com');
        $this->assertNotNull($carlos, 'Cliente Carlos não encontrado na lista');
        $this->assertEquals('Carlos', $carlos['name']);
        $this->assertEquals('44444444444', $carlos['cpf']);
    }
}
