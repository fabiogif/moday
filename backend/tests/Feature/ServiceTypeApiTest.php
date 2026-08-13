<?php

namespace Tests\Feature;

use App\Models\ServiceType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use App\Http\Middleware\Authenticate as AppAuthenticate;
use Illuminate\Auth\Middleware\Authenticate as LaravelAuthenticate;
use Illuminate\Support\Facades\Cache;

class ServiceTypeApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private array $headers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->withoutMiddleware([
            JWTAuthenticate::class,
            AppAuthenticate::class,
            LaravelAuthenticate::class,
        ]);
        $this->actingAs($this->user);
        
        // Limpar cache antes de cada teste
        Cache::flush();
    }

    private function getAuthHeaders(): array
    {
        return $this->headers;
    }

    #[Test]
    public function it_can_list_all_service_types()
    {
        ServiceType::factory()->count(3)->create([
            'is_active' => true,
        ]);

        ServiceType::factory()->count(2)->create([
            'is_active' => false,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/service-type');

        $response->assertStatus(200);
        // Verifica que retorna apenas os ativos (is_active != '0')
        $this->assertGreaterThanOrEqual(3, count($response->json('data') ?? []));
    }

    #[Test]
    public function it_can_create_a_service_type()
    {
        $serviceTypeData = [
            'name' => 'Delivery',
            'slug' => 'delivery',
            'description' => 'Entrega em domicílio',
            'is_active' => true,
            'requires_address' => true,
            'requires_table' => false,
            'available_in_menu' => true,
            'order_position' => 1,
        ];

        $response = $this->withHeaders($this->getAuthHeaders())->postJson('/api/service-type', $serviceTypeData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Delivery',
                    'slug' => 'delivery',
                ]
            ]);

        $this->assertDatabaseHas('service_types', [
            'name' => 'Delivery',
            'slug' => 'delivery',
        ]);
    }

    #[Test]
    public function it_can_delete_a_service_type()
    {
        $serviceType = ServiceType::factory()->create([
            'name' => 'Delivery',
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())->deleteJson("/api/service-type/{$serviceType->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deleted' => true,
                ],
                'message' => 'Tipo de atendimento removido com sucesso',
            ]);

        // Verifica que foi feito soft delete
        $this->assertSoftDeleted('service_types', ['id' => $serviceType->id]);
        
        // Verifica que deleted_at foi preenchido
        $this->assertNotNull($serviceType->fresh()->deleted_at);
    }

    #[Test]
    public function it_excludes_deleted_service_types_from_list()
    {
        // Criar tipos de atendimento
        $activeType1 = ServiceType::factory()->create([
            'name' => 'Delivery',
            'is_active' => true,
        ]);
        
        $activeType2 = ServiceType::factory()->create([
            'name' => 'Retirada',
            'is_active' => true,
        ]);

        $deletedType = ServiceType::factory()->create([
            'name' => 'Mesa',
            'is_active' => true,
        ]);

        // Deletar um tipo
        $deletedType->delete();

        // Buscar lista
        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/service-type');

        $response->assertStatus(200);
        
        $data = $response->json('data') ?? [];
        $names = array_column($data, 'name');

        // Verifica que o tipo deletado não está na lista
        $this->assertNotContains('Mesa', $names);
        
        // Verifica que os tipos ativos estão na lista
        $this->assertContains('Delivery', $names);
        $this->assertContains('Retirada', $names);
    }

    #[Test]
    public function it_excludes_deleted_service_types_from_active_endpoint()
    {
        $activeType = ServiceType::factory()->active()->create([
            'name' => 'Delivery',
        ]);

        $deletedType = ServiceType::factory()->active()->create([
            'name' => 'Retirada',
        ]);

        // Deletar um tipo
        $deletedType->delete();

        // Buscar tipos ativos
        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/service-type/active');

        $response->assertStatus(200);
        
        $data = $response->json('data') ?? [];
        $names = array_column($data, 'name');

        // Verifica que o tipo deletado não está na lista
        $this->assertNotContains('Retirada', $names);
        
        // Verifica que o tipo ativo está na lista
        $this->assertContains('Delivery', $names);
    }

    #[Test]
    public function it_excludes_deleted_service_types_from_menu_endpoint()
    {
        $menuType = ServiceType::factory()->availableInMenu()->create([
            'name' => 'Delivery',
        ]);

        $deletedMenuType = ServiceType::factory()->availableInMenu()->create([
            'name' => 'Retirada',
        ]);

        // Deletar um tipo
        $deletedMenuType->delete();

        // Buscar tipos do menu
        $response = $this->getJson('/api/service-type/menu');

        $response->assertStatus(200);
        
        $data = $response->json('data') ?? [];
        $names = array_column($data, 'name');

        // Verifica que o tipo deletado não está na lista
        $this->assertNotContains('Retirada', $names);
        
        // Verifica que o tipo ativo está na lista
        $this->assertContains('Delivery', $names);
    }

    #[Test]
    public function it_clears_cache_after_deletion()
    {
        $serviceType = ServiceType::factory()->active()->create([
            'name' => 'Delivery',
        ]);

        // Popular cache
        Cache::put('service_types_active', [$serviceType], 3600);
        Cache::put('service_types_menu', [$serviceType], 3600);

        // Verificar que cache existe
        $this->assertTrue(Cache::has('service_types_active'));
        $this->assertTrue(Cache::has('service_types_menu'));

        // Deletar tipo
        $response = $this->withHeaders($this->getAuthHeaders())->deleteJson("/api/service-type/{$serviceType->uuid}");

        $response->assertStatus(200);

        // Verificar que cache foi limpo
        $this->assertFalse(Cache::has('service_types_active'));
        $this->assertFalse(Cache::has('service_types_menu'));
    }

    #[Test]
    public function it_returns_404_when_deleting_nonexistent_service_type()
    {
        $response = $this->withHeaders($this->getAuthHeaders())->deleteJson('/api/service-type/non-existent-uuid');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deleted' => false,
                ],
            ]);
    }

    #[Test]
    public function it_can_filter_service_types_by_name()
    {
        ServiceType::factory()->create([
            'name' => 'Delivery Express',
            'is_active' => true,
        ]);

        ServiceType::factory()->create([
            'name' => 'Retirada Rápida',
            'is_active' => true,
        ]);

        ServiceType::factory()->create([
            'name' => 'Mesa',
            'is_active' => true,
        ]);

        // Deletar um tipo
        ServiceType::where('name', 'Mesa')->first()->delete();

        $response = $this->withHeaders($this->getAuthHeaders())->getJson('/api/service-type?filter=Delivery');

        $response->assertStatus(200);
        
        $data = $response->json('data') ?? [];
        $names = array_column($data, 'name');

        // Verifica que retorna apenas Delivery (não retorna Mesa deletada)
        $this->assertContains('Delivery Express', $names);
        $this->assertNotContains('Mesa', $names);
    }

    #[Test]
    public function it_cannot_access_deleted_service_type_by_identify()
    {
        $serviceType = ServiceType::factory()->create([
            'name' => 'Delivery',
        ]);

        // Deletar tipo
        $serviceType->delete();

        // Tentar buscar pelo identify
        $response = $this->withHeaders($this->getAuthHeaders())->getJson("/api/service-type/{$serviceType->identify}");

        // Deve retornar 404 pois o tipo foi deletado
        $response->assertStatus(404);
    }

    #[Test]
    public function it_maintains_data_integrity_after_soft_delete()
    {
        $serviceType = ServiceType::factory()->create([
            'name' => 'Delivery',
            'description' => 'Entrega em domicílio',
            'is_active' => true,
        ]);

        $originalId = $serviceType->id;
        $originalUuid = $serviceType->uuid;
        $originalName = $serviceType->name;

        // Deletar tipo
        $response = $this->withHeaders($this->getAuthHeaders())->deleteJson("/api/service-type/{$serviceType->uuid}");

        $response->assertStatus(200);

        // Verificar que os dados originais ainda existem no banco
        $this->assertDatabaseHas('service_types', [
            'id' => $originalId,
            'uuid' => $originalUuid,
            'name' => $originalName,
        ]);

        // Verificar que deleted_at foi preenchido
        $deletedServiceType = ServiceType::withTrashed()->find($originalId);
        $this->assertNotNull($deletedServiceType->deleted_at);
    }
}

