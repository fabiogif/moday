<?php

namespace Tests\Feature;

use App\Models\StoreHour;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreHourApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Loja Teste',
            'slug' => 'loja-teste',
            'email' => 'loja@teste.com',
            'cnpj' => '12345678000199',
            'is_active' => true,
        ]);

        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'usuario@teste.com',
        ]);

        // Obter token
        $response = $this->postJson('/api/auth/login', [
            'email' => 'usuario@teste.com',
            'password' => 'password',
        ]);

        $this->token = $response->json('data.token');
    }

    #[Test]
    public function it_can_list_store_hours()
    {
        // Criar horários
        StoreHour::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/store-hours');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'tenant_id',
                        'is_always_open',
                        'day_of_week',
                        'day_name',
                        'delivery_type',
                        'start_time',
                        'end_time',
                        'is_active',
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_can_create_a_store_hour()
    {
        $data = [
            'day_of_week' => 1, // Segunda-feira
            'delivery_type' => 'both',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'is_active' => true,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/store-hours', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Horário de funcionamento criado com sucesso',
            ]);

        $this->assertDatabaseHas('store_hours', [
            'tenant_id' => $this->tenant->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/store-hours', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['delivery_type']);
    }

    #[Test]
    public function it_validates_time_format()
    {
        $data = [
            'day_of_week' => 1,
            'delivery_type' => 'both',
            'start_time' => 'invalid',
            'end_time' => 'invalid',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/store-hours', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time', 'end_time']);
    }

    #[Test]
    public function it_validates_end_time_is_after_start_time()
    {
        $data = [
            'day_of_week' => 1,
            'delivery_type' => 'both',
            'start_time' => '18:00',
            'end_time' => '08:00', // Antes do start_time
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/store-hours', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    }

    #[Test]
    public function it_prevents_overlapping_hours()
    {
        // Criar horário existente
        StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'is_active' => true,
        ]);

        // Tentar criar horário sobreposto
        $data = [
            'day_of_week' => 1,
            'delivery_type' => 'both',
            'start_time' => '10:00', // Sobrepõe o horário existente
            'end_time' => '14:00',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/store-hours', $data);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    #[Test]
    public function it_can_update_a_store_hour()
    {
        $storeHour = StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '18:00',
        ]);

        $data = [
            'start_time' => '09:00',
            'end_time' => '17:00',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/store-hours/{$storeHour->uuid}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Horário de funcionamento atualizado com sucesso',
            ]);

        $this->assertDatabaseHas('store_hours', [
            'id' => $storeHour->id,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
    }

    #[Test]
    public function it_can_delete_a_store_hour()
    {
        $storeHour = StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/store-hours/{$storeHour->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Horário de funcionamento excluído com sucesso',
            ]);

        $this->assertSoftDeleted('store_hours', [
            'id' => $storeHour->id,
        ]);
    }

    #[Test]
    public function it_can_show_a_specific_store_hour()
    {
        $storeHour = StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/store-hours/{$storeHour->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $storeHour->uuid,
                    'day_of_week' => $storeHour->day_of_week,
                ]
            ]);
    }

    #[Test]
    public function it_can_filter_by_day_of_week()
    {
        StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
            'day_of_week' => 1, // Segunda
        ]);

        StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
            'day_of_week' => 2, // Terça
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/store-hours?day_of_week=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]['day_of_week']);
    }

    #[Test]
    public function it_can_filter_by_delivery_type()
    {
        StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
            'delivery_type' => 'delivery',
        ]);

        StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
            'delivery_type' => 'pickup',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/store-hours?delivery_type=delivery');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertGreaterThan(0, count($data));
    }

    #[Test]
    public function it_can_set_always_open()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/store-hours/set-always-open', [
                'delivery_type' => 'both',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Loja configurada como sempre aberta',
            ]);

        $this->assertDatabaseHas('store_hours', [
            'tenant_id' => $this->tenant->id,
            'is_always_open' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_can_remove_always_open()
    {
        // Criar configuração "sempre aberto"
        StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_always_open' => true,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/store-hours/remove-always-open');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Configuração "sempre aberto" removida com sucesso',
            ]);

        $this->assertDatabaseHas('store_hours', [
            'tenant_id' => $this->tenant->id,
            'is_always_open' => true,
            'is_active' => false, // Desativado
        ]);
    }

    #[Test]
    public function it_can_check_if_store_is_open()
    {
        // Criar horário para hoje
        $now = now();
        $dayOfWeek = $now->dayOfWeek;
        $currentTime = $now->format('H:i');
        
        StoreHour::factory()->create([
            'tenant_id' => $this->tenant->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $now->copy()->subHour()->format('H:i'),
            'end_time' => $now->copy()->addHour()->format('H:i'),
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/store-hours/check-is-open');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_open' => true,
                ]
            ]);
    }

    #[Test]
    public function it_returns_stats()
    {
        StoreHour::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'delivery_type' => 'both',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/store-hours/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'is_always_open',
                    'total_hours',
                    'days_configured',
                    'has_delivery',
                    'has_pickup',
                ]
            ]);
    }

    #[Test]
    public function it_isolates_data_by_tenant()
    {
        // Criar outro tenant
        $otherTenant = Tenant::factory()->create([
            'name' => 'Outra Loja',
            'slug' => 'outra-loja',
            'email' => 'outra@teste.com',
            'cnpj' => '22345678000199',
            'is_active' => true,
        ]);

        // Criar horários para outro tenant
        StoreHour::factory()->count(3)->create([
            'tenant_id' => $otherTenant->id,
        ]);

        // Listar horários do tenant atual
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/store-hours');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Não deve retornar horários de outro tenant
        $this->assertCount(0, $data);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/store-hours');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_validates_day_of_week_range()
    {
        $data = [
            'day_of_week' => 7, // Inválido (deve ser 0-6)
            'delivery_type' => 'both',
            'start_time' => '08:00',
            'end_time' => '18:00',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/store-hours', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['day_of_week']);
    }

    #[Test]
    public function it_validates_delivery_type_values()
    {
        $data = [
            'day_of_week' => 1,
            'delivery_type' => 'invalid', // Valor inválido
            'start_time' => '08:00',
            'end_time' => '18:00',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/store-hours', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['delivery_type']);
    }
}

