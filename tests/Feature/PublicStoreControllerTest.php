<?php

namespace Tests\Feature;

use App\Services\PublicOrderService;
use App\Repositories\Contracts\PublicStoreRepositoryInterface;
use App\Models\Tenant;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Mail\OrderCompletedMail;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicStoreControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Evita erros de escrita em arquivo de log no sandbox
        Config::set('logging.default', 'stderr');
    }

    public function test_get_store_info_success()
    {
        $slug = 'empresa-dev';
        $tenant = (new Tenant())->forceFill([
            'id' => 1,
            'uuid' => 'tenant-uuid',
            'name' => 'Empresa Dev',
            'slug' => $slug,
            'email' => 'contato@empresa.dev',
            'phone' => '11999999999',
            'logo' => 'https://cdn/logo.png',
            'settings' => []
        ]);
        app()->bind(PublicStoreRepositoryInterface::class, function () use ($tenant) {
            return new class($tenant) implements PublicStoreRepositoryInterface {
                public function __construct(private Tenant $tenant) {}
                public function getTenantBySlug(string $slug): ?Tenant { return $this->tenant; }
                public function getActiveProducts(int $tenantId): array { return []; }
                public function getActivePaymentMethods(int $tenantId): array { return []; }
            };
        });

        $res = $this->getJson("/api/store/{$slug}/info");
        $res->assertStatus(200)
            ->assertJson([ 'success' => true ]);
    }

    public function test_get_store_info_not_found()
    {
        $slug = 'inexistente';
        app()->bind(PublicStoreRepositoryInterface::class, function () {
            return new class implements PublicStoreRepositoryInterface {
                public function getTenantBySlug(string $slug): ?Tenant { return null; }
                public function getActiveProducts(int $tenantId): array { return []; }
                public function getActivePaymentMethods(int $tenantId): array { return []; }
            };
        });
        $this->getJson("/api/store/{$slug}/info")
            ->assertStatus(404);
    }

    public function test_get_products_success()
    {
        $slug = 'empresa-dev';
        $tenantId = 1;
        $products = [
            ['id' => 1, 'name' => 'Produto A', 'image' => null],
            ['id' => 2, 'name' => 'Produto B', 'image' => null],
        ];
        app()->bind(PublicStoreRepositoryInterface::class, function () use ($products, $tenantId, $slug) {
            return new class($products, $tenantId, $slug) implements PublicStoreRepositoryInterface {
                public function __construct(private array $products, private int $tenantId, private string $slug) {}
                public function getTenantBySlug(string $slug): ?Tenant { return (new Tenant())->forceFill(['id' => $this->tenantId, 'slug' => $this->slug]); }
                public function getActiveProducts(int $tenantId): array { return $this->products; }
                public function getActivePaymentMethods(int $tenantId): array { return []; }
            };
        });

        $this->getJson("/api/store/{$slug}/products")
            ->assertStatus(200)
            ->assertJson([ 'success' => true ]);
    }

    public function test_get_products_store_not_found()
    {
        $slug = 'inexistente';
        app()->bind(PublicStoreRepositoryInterface::class, function () {
            return new class implements PublicStoreRepositoryInterface {
                public function getTenantBySlug(string $slug): ?Tenant { return null; }
                public function getActiveProducts(int $tenantId): array { return []; }
                public function getActivePaymentMethods(int $tenantId): array { return []; }
            };
        });
        $this->getJson("/api/store/{$slug}/products")
            ->assertStatus(404);
    }

    public function test_get_payment_methods_success()
    {
        $slug = 'empresa-dev';
        $tenantId = 1;
        $methods = [
            ['uuid' => 'pm-1', 'name' => 'PIX'],
            ['uuid' => 'pm-2', 'name' => 'Cartão'],
        ];
        app()->bind(PublicStoreRepositoryInterface::class, function () use ($methods, $tenantId, $slug) {
            return new class($methods, $tenantId, $slug) implements PublicStoreRepositoryInterface {
                public function __construct(private array $methods, private int $tenantId, private string $slug) {}
                public function getTenantBySlug(string $slug): ?Tenant { return (new Tenant())->forceFill(['id' => $this->tenantId, 'slug' => $this->slug]); }
                public function getActiveProducts(int $tenantId): array { return []; }
                public function getActivePaymentMethods(int $tenantId): array { return $this->methods; }
            };
        });

        $this->getJson("/api/store/{$slug}/payment-methods")
            ->assertStatus(200)
            ->assertJson([ 'success' => true ]);
    }

    public function test_get_payment_methods_store_not_found()
    {
        $slug = 'inexistente';
        app()->bind(PublicStoreRepositoryInterface::class, function () {
            return new class implements PublicStoreRepositoryInterface {
                public function getTenantBySlug(string $slug): ?Tenant { return null; }
                public function getActiveProducts(int $tenantId): array { return []; }
                public function getActivePaymentMethods(int $tenantId): array { return []; }
            };
        });
        $this->getJson("/api/store/{$slug}/payment-methods")
            ->assertStatus(404);
    }

    public function test_create_order_success()
    {
        $slug = 'empresa-dev';

        // Seed mínimo para passar na validação do FormRequest
        $plan = Plan::factory()->create([
            'name' => 'Basic',
            'url' => 'basic',
            'description' => 'Plano básico',
            'price' => 0,
            'is_active' => true,
            'max_users' => 10,
            'max_products' => 100,
            'max_orders_per_month' => 1000,
        ]);
        $tenant = Tenant::factory()->accessible()->create([
            'name' => 'Empresa Dev',
            'slug' => $slug,
            'email' => 'contato@empresa.dev',
            'phone' => '11999999999',
            'is_active' => true,
            'plan_id' => $plan->id,
            'url' => $slug,
            'cnpj' => '12.345.678/0001-90',
        ]);
        $pm = PaymentMethod::create([
            'uuid' => 'pm-uuid',
            'name' => 'PIX',
            'description' => 'Pagamento via PIX',
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $product = Product::create([
            'uuid' => 'prod-1',
            'name' => 'Produto A',
            'price' => 10.0,
            'price_cost' => 5.0,
            'qtd_stock' => 10,
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        app()->bind(PublicStoreRepositoryInterface::class, function () use ($tenant) {
            return new class($tenant) implements PublicStoreRepositoryInterface {
                public function __construct(private Tenant $tenant) {}
                public function getTenantBySlug(string $slug): ?Tenant { return $this->tenant; }
                public function getActiveProducts(int $tenantId): array { return []; }
                public function getActivePaymentMethods(int $tenantId): array { return []; }
            };
        });

        \Illuminate\Support\Facades\DB::table('order_statuses')->insert([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Pedido Recebido',
            'slug' => 'pedido-recebido',
            'description' => null,
            'color' => '#3B82F6',
            'icon' => 'package',
            'order_position' => 1,
            'is_initial' => true,
            'is_final' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'client' => [
                'name' => 'João',
                'email' => 'joao@example.com',
                'phone' => '11999999999'
            ],
            'products' => [
                ['uuid' => 'prod-1', 'quantity' => 1],
                ['uuid' => 'prod-1', 'quantity' => 2],
            ],
            'payment_method' => 'pm-uuid',
            'shipping_method' => 'pickup',
            'delivery' => [
                'is_delivery' => false
            ]
        ];

        $this->postJson("/api/store/{$slug}/orders", $payload)
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_id', fn ($id) => !empty($id));

        $this->assertDatabaseHas('order_product', [
            'product_id' => $product->id,
            'qty' => 3,
        ]);

        $this->assertDatabaseHas('orders', [
            'tenant_id' => $tenant->id,
            'is_delivery' => false,
            'shipping_method' => 'pickup',
        ]);
    }

    public function test_create_order_sends_completion_email_when_plan_has_feature(): void
    {
        Mail::fake();

        $slug = 'empresa-email';
        $plan = Plan::factory()->create([
            'has_order_completion_email' => true,
        ]);
        $tenant = Tenant::factory()->accessible()->create([
            'slug' => $slug,
            'plan_id' => $plan->id,
            'url' => $slug,
            'is_active' => true,
        ]);
        $paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'qtd_stock' => 10,
            'is_active' => true,
        ]);

        app()->bind(PublicStoreRepositoryInterface::class, function () use ($tenant) {
            return new class($tenant) implements PublicStoreRepositoryInterface {
                public function __construct(private Tenant $tenant) {}
                public function getTenantBySlug(string $slug): ?Tenant { return $this->tenant; }
                public function getActiveProducts(int $tenantId): array { return []; }
                public function getActivePaymentMethods(int $tenantId): array { return []; }
            };
        });

        \Illuminate\Support\Facades\DB::table('order_statuses')->insert([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Pedido Recebido',
            'slug' => 'pedido-recebido',
            'description' => null,
            'color' => '#3B82F6',
            'icon' => 'package',
            'order_position' => 1,
            'is_initial' => true,
            'is_final' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'client' => [
                'name' => 'Maria',
                'email' => 'maria@example.com',
                'phone' => '11999999999',
            ],
            'products' => [
                ['uuid' => $product->uuid, 'quantity' => 1],
            ],
            'payment_method' => $paymentMethod->uuid,
            'shipping_method' => 'pickup',
            'delivery' => ['is_delivery' => false],
        ];

        $this->postJson("/api/store/{$slug}/orders", $payload)
            ->assertStatus(201);

        Mail::assertSent(OrderCompletedMail::class);
    }

    public function test_create_order_skips_completion_email_without_plan_feature(): void
    {
        Mail::fake();

        $slug = 'empresa-sem-email';
        $plan = Plan::factory()->create([
            'has_order_completion_email' => false,
        ]);
        $tenant = Tenant::factory()->accessible()->create([
            'slug' => $slug,
            'plan_id' => $plan->id,
            'url' => $slug,
            'is_active' => true,
        ]);
        $paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'qtd_stock' => 10,
            'is_active' => true,
        ]);

        app()->bind(PublicStoreRepositoryInterface::class, function () use ($tenant) {
            return new class($tenant) implements PublicStoreRepositoryInterface {
                public function __construct(private Tenant $tenant) {}
                public function getTenantBySlug(string $slug): ?Tenant { return $this->tenant; }
                public function getActiveProducts(int $tenantId): array { return []; }
                public function getActivePaymentMethods(int $tenantId): array { return []; }
            };
        });

        \Illuminate\Support\Facades\DB::table('order_statuses')->insert([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Pedido Recebido',
            'slug' => 'pedido-recebido',
            'description' => null,
            'color' => '#3B82F6',
            'icon' => 'package',
            'order_position' => 1,
            'is_initial' => true,
            'is_final' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'client' => [
                'name' => 'João',
                'email' => 'joao@example.com',
                'phone' => '11999999999',
            ],
            'products' => [
                ['uuid' => $product->uuid, 'quantity' => 1],
            ],
            'payment_method' => $paymentMethod->uuid,
            'shipping_method' => 'pickup',
            'delivery' => ['is_delivery' => false],
        ];

        $this->postJson("/api/store/{$slug}/orders", $payload)
            ->assertStatus(201);

        Mail::assertNotSent(OrderCompletedMail::class);
    }

    public function test_create_order_store_not_found()
    {
        $slug = 'inexistente';
        app()->bind(PublicStoreRepositoryInterface::class, function () {
            return new class implements PublicStoreRepositoryInterface {
                public function getTenantBySlug(string $slug): ?Tenant { return null; }
                public function getActiveProducts(int $tenantId): array { return []; }
                public function getActivePaymentMethods(int $tenantId): array { return []; }
            };
        });

        $payload = [
            'client' => [
                'name' => 'João',
                'email' => 'joao@example.com',
                'phone' => '11999999999'
            ],
            'products' => [
                ['uuid' => 'prod-1', 'quantity' => 1]
            ],
            'payment_method' => 'pm-uuid',
            'shipping_method' => 'pickup',
            'delivery' => [
                'is_delivery' => false
            ]
        ];

        $this->postJson("/api/store/{$slug}/orders", $payload)
            ->assertStatus(422); // validação do Request retorna 422 com erro de loja não encontrada
    }
}


