<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ClientCpfFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('logging.default', 'stderr');
    }

    public function test_admin_panel_check_cpf_returns_existing_client()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'profile_id' => $profile->id]);
        
        $client = Client::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11999999999',
            'cpf' => '12345678900',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/client/check-cpf?cpf=12345678900');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exists' => true,
                ],
            ]);
    }

    public function test_admin_panel_check_cpf_returns_not_found()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'profile_id' => $profile->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/client/check-cpf?cpf=99999999999');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exists' => false,
                    'client' => null,
                ],
            ]);
    }

    public function test_public_store_updates_existing_client_by_cpf()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create([
            'plan_id' => $plan->id,
            'slug' => 'test-store',
            'phone' => '11999999999',
        ]);
        
        // Cliente existente
        $existingClient = Client::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Maria Antiga',
            'email' => 'maria.antiga@example.com',
            'phone' => '11888888888',
            'cpf' => '12345678900',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Dados do pedido com mesmo CPF mas dados diferentes
        $orderData = [
            'client' => [
                'name' => 'Maria Atualizada',
                'email' => 'maria.nova@example.com',
                'phone' => '11777777777',
                'cpf' => '12345678900', // Mesmo CPF
            ],
            'products' => [
                ['uuid' => 'prod-uuid', 'quantity' => 1]
            ],
            'payment_method' => 'pm-uuid',
            'shipping_method' => 'pickup',
            'delivery' => [
                'is_delivery' => false
            ]
        ];

        // Fazer a chamada (vai falhar na validação, mas o importante é testar o service)
        // Em vez disso, vamos testar diretamente o service
        $service = app(\App\Services\PublicClientService::class);
        $updatedClient = $service->createOrUpdateClient($orderData['client'], $tenant);

        // Deve ser o mesmo cliente (não criar novo)
        $this->assertEquals($existingClient->id, $updatedClient->id);
        
        // Dados devem estar atualizados
        $this->assertEquals('Maria Atualizada', $updatedClient->name);
        $this->assertEquals('maria.nova@example.com', $updatedClient->email);
        $this->assertEquals('11777777777', $updatedClient->phone);
        
        // Deve ter apenas 1 cliente com esse CPF
        $this->assertEquals(1, Client::where('cpf', '12345678900')->count());
    }

    public function test_public_store_creates_new_client_when_cpf_not_found()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create([
            'plan_id' => $plan->id,
            'slug' => 'test-store',
            'phone' => '11999999999',
        ]);

        $clientData = [
            'name' => 'Novo Cliente',
            'email' => 'novo@example.com',
            'phone' => '11666666666',
            'cpf' => '99999999999',
        ];

        $service = app(\App\Services\PublicClientService::class);
        $newClient = $service->createOrUpdateClient($clientData, $tenant);

        $this->assertNotNull($newClient->id);
        $this->assertEquals('Novo Cliente', $newClient->name);
        $this->assertEquals('novo@example.com', $newClient->email);
        $this->assertEquals('99999999999', $newClient->cpf);
        
        // Deve ter criado apenas 1 cliente
        $this->assertEquals(1, Client::where('cpf', '99999999999')->count());
    }

    public function test_public_store_creates_client_without_cpf()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create([
            'plan_id' => $plan->id,
            'slug' => 'test-store',
            'phone' => '11999999999',
        ]);

        $clientData = [
            'name' => 'Cliente Sem CPF',
            'email' => 'sem.cpf@example.com',
            'phone' => '11555555555',
        ];

        $service = app(\App\Services\PublicClientService::class);
        $newClient = $service->createOrUpdateClient($clientData, $tenant);

        $this->assertNotNull($newClient->id);
        $this->assertEquals('Cliente Sem CPF', $newClient->name);
        $this->assertNull($newClient->cpf);
    }

    public function test_client_service_find_by_cpf_and_tenant()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        
        $client = Client::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'phone' => '11444444444',
            'cpf' => '11122233344',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $service = app(\App\Services\ClientService::class);
        $found = $service->findByCpfAndTenant('11122233344', $tenant->id);

        $this->assertNotNull($found);
        $this->assertEquals($client->id, $found->id);
        
        // CPF de outro tenant não deve ser encontrado
        $tenant2 = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $notFound = $service->findByCpfAndTenant('11122233344', $tenant2->id);
        $this->assertNull($notFound);
    }

    public function test_admin_panel_check_cpf_requires_authentication()
    {
        $response = $this->getJson('/api/client/check-cpf?cpf=12345678900');
        
        $response->assertStatus(401);
    }

    public function test_admin_panel_check_cpf_requires_cpf_parameter()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'profile_id' => $profile->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/client/check-cpf');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpf']);
    }

    public function test_admin_panel_check_cpf_isolates_by_tenant()
    {
        $plan = Plan::factory()->create();
        
        // Tenant 1 com cliente
        $tenant1 = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $profile1 = Profile::factory()->create(['tenant_id' => $tenant1->id]);
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id, 'profile_id' => $profile1->id]);
        
        Client::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenant1->id,
            'name' => 'Cliente Tenant 1',
            'email' => 'cliente1@example.com',
            'phone' => '11111111111',
            'cpf' => '12345678900',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Tenant 2 sem cliente com esse CPF
        $tenant2 = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $profile2 = Profile::factory()->create(['tenant_id' => $tenant2->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id, 'profile_id' => $profile2->id]);

        // User do tenant 2 não deve encontrar o cliente do tenant 1
        $response = $this->actingAs($user2, 'api')
            ->getJson('/api/client/check-cpf?cpf=12345678900');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exists' => false,
                    'client' => null,
                ],
            ]);
    }

    public function test_public_store_preserves_client_id_when_updating_by_cpf()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create([
            'plan_id' => $plan->id,
            'slug' => 'test-store',
            'phone' => '11999999999',
        ]);
        
        $originalId = Client::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Nome Original',
            'email' => 'original@example.com',
            'phone' => '11000000000',
            'cpf' => '11111111111',
            'password' => bcrypt('password'),
            'is_active' => true,
        ])->id;

        $service = app(\App\Services\PublicClientService::class);
        
        // Primeira atualização
        $updated1 = $service->createOrUpdateClient([
            'name' => 'Atualização 1',
            'email' => 'update1@example.com',
            'phone' => '11111111111',
            'cpf' => '11111111111',
        ], $tenant);

        $this->assertEquals($originalId, $updated1->id);
        $this->assertEquals('Atualização 1', $updated1->name);

        // Segunda atualização
        $updated2 = $service->createOrUpdateClient([
            'name' => 'Atualização 2',
            'email' => 'update2@example.com',
            'phone' => '11222222222',
            'cpf' => '11111111111',
        ], $tenant);

        $this->assertEquals($originalId, $updated2->id);
        $this->assertEquals('Atualização 2', $updated2->name);
        $this->assertEquals('11222222222', $updated2->phone);

        // Confirma que só existe 1 registro com esse CPF
        $this->assertEquals(1, Client::where('cpf', '11111111111')->count());
    }

    public function test_public_store_handles_multiple_clients_with_different_cpfs()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create([
            'plan_id' => $plan->id,
            'slug' => 'test-store',
            'phone' => '11999999999',
        ]);

        $service = app(\App\Services\PublicClientService::class);

        // Criar 3 clientes diferentes
        $client1 = $service->createOrUpdateClient([
            'name' => 'Cliente 1',
            'email' => 'cliente1@example.com',
            'phone' => '11111111111',
            'cpf' => '11111111111',
        ], $tenant);

        $client2 = $service->createOrUpdateClient([
            'name' => 'Cliente 2',
            'email' => 'cliente2@example.com',
            'phone' => '11222222222',
            'cpf' => '22222222222',
        ], $tenant);

        $client3 = $service->createOrUpdateClient([
            'name' => 'Cliente 3',
            'email' => 'cliente3@example.com',
            'phone' => '11333333333',
            'cpf' => '33333333333',
        ], $tenant);

        // Todos devem ter IDs diferentes
        $this->assertNotEquals($client1->id, $client2->id);
        $this->assertNotEquals($client2->id, $client3->id);
        $this->assertNotEquals($client1->id, $client3->id);

        // Total de clientes deve ser 3
        $this->assertEquals(3, Client::where('tenant_id', $tenant->id)->count());
    }

    public function test_public_store_updates_email_and_phone_when_cpf_matches()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create([
            'plan_id' => $plan->id,
            'slug' => 'test-store',
            'phone' => '11999999999',
        ]);
        
        // Cliente inicial
        Client::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'João Silva',
            'email' => 'joao.antigo@example.com',
            'phone' => '11888888888',
            'cpf' => '12312312312',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $service = app(\App\Services\PublicClientService::class);
        
        // Atualizar com novos dados
        $updated = $service->createOrUpdateClient([
            'name' => 'João Silva Atualizado',
            'email' => 'joao.novo@example.com',
            'phone' => '11777777777',
            'cpf' => '12312312312',
        ], $tenant);

        // Verificar se todos os campos foram atualizados
        $this->assertEquals('João Silva Atualizado', $updated->name);
        $this->assertEquals('joao.novo@example.com', $updated->email);
        $this->assertEquals('11777777777', $updated->phone);
        $this->assertEquals('12312312312', $updated->cpf);
    }

    public function test_client_service_update_or_create_by_cpf_returns_existed_flag()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);

        $service = app(\App\Services\ClientService::class);

        // Primeiro cadastro - não existia
        $result1 = $service->updateOrCreateByCpf([
            'name' => 'Novo Cliente',
            'email' => 'novo@example.com',
            'phone' => '11555555555',
            'cpf' => '55555555555',
            'password' => 'password123',
        ], $tenant->id);

        $this->assertFalse($result1['existed']);
        $this->assertNotNull($result1['client']);
        $this->assertEquals('Novo Cliente', $result1['client']->name);

        // Segunda chamada com mesmo CPF - já existia
        $result2 = $service->updateOrCreateByCpf([
            'name' => 'Cliente Atualizado',
            'email' => 'atualizado@example.com',
            'phone' => '11666666666',
            'cpf' => '55555555555',
            'password' => 'newpassword',
        ], $tenant->id);

        $this->assertTrue($result2['existed']);
        $this->assertEquals($result1['client']->id, $result2['client']->id);
        $this->assertEquals('Cliente Atualizado', $result2['client']->name);
    }

    public function test_client_service_update_or_create_without_cpf_always_creates_new()
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);

        $service = app(\App\Services\ClientService::class);

        // Criar 2 clientes sem CPF
        $result1 = $service->updateOrCreateByCpf([
            'name' => 'Cliente Sem CPF 1',
            'email' => 'sem.cpf1@example.com',
            'phone' => '11111111111',
            'password' => 'password',
        ], $tenant->id);

        $result2 = $service->updateOrCreateByCpf([
            'name' => 'Cliente Sem CPF 2',
            'email' => 'sem.cpf2@example.com',
            'phone' => '11222222222',
            'password' => 'password',
        ], $tenant->id);

        // Ambos não existiam
        $this->assertFalse($result1['existed']);
        $this->assertFalse($result2['existed']);

        // Devem ter IDs diferentes
        $this->assertNotEquals($result1['client']->id, $result2['client']->id);

        // Total de clientes sem CPF deve ser 2
        $this->assertEquals(2, Client::whereNull('cpf')->where('tenant_id', $tenant->id)->count());
    }

    public function test_public_store_does_not_leak_clients_across_tenants()
    {
        $plan = Plan::factory()->create();
        
        $tenant1 = Tenant::factory()->accessible()->create([
            'plan_id' => $plan->id,
            'slug' => 'store-1',
        ]);
        
        $tenant2 = Tenant::factory()->accessible()->create([
            'plan_id' => $plan->id,
            'slug' => 'store-2',
        ]);

        $service = app(\App\Services\PublicClientService::class);

        // Criar cliente no tenant 1
        $client1 = $service->createOrUpdateClient([
            'name' => 'Cliente Store 1',
            'email' => 'cliente@store1.com',
            'phone' => '11111111111',
            'cpf' => '99999999999',
        ], $tenant1);

        // Criar cliente com mesmo CPF no tenant 2 (deve criar novo)
        $client2 = $service->createOrUpdateClient([
            'name' => 'Cliente Store 2',
            'email' => 'cliente@store2.com',
            'phone' => '11222222222',
            'cpf' => '99999999999',
        ], $tenant2);

        // Devem ser clientes diferentes
        $this->assertNotEquals($client1->id, $client2->id);
        $this->assertEquals($tenant1->id, $client1->tenant_id);
        $this->assertEquals($tenant2->id, $client2->tenant_id);

        // Cada tenant deve ter 1 cliente com esse CPF
        $this->assertEquals(1, Client::where('cpf', '99999999999')->where('tenant_id', $tenant1->id)->count());
        $this->assertEquals(1, Client::where('cpf', '99999999999')->where('tenant_id', $tenant2->id)->count());
    }
}

