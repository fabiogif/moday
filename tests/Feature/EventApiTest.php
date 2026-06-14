<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('logging.default', 'stderr');
    }

    private function actingTenantUser(): array
    {
        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->create(['plan_id' => $plan->id, 'is_active' => true]);
        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'profile_id' => $profile->id]);
        $this->actingAs($user, 'api');
        return [$tenant, $user];
    }

    public function test_can_list_events_for_tenant()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        Event::factory()->count(3)->create(['tenant_id' => $tenant->id]);
        Event::factory()->count(2)->create(); // outros tenants

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_events_by_date_range()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        Event::factory()->create([
            'tenant_id' => $tenant->id,
            'start_date' => '2025-11-01 10:00:00',
        ]);

        Event::factory()->create([
            'tenant_id' => $tenant->id,
            'start_date' => '2025-11-15 14:00:00',
        ]);

        Event::factory()->create([
            'tenant_id' => $tenant->id,
            'start_date' => '2025-12-01 10:00:00',
        ]);

        $response = $this->getJson('/api/events?start_date=2025-11-01&end_date=2025-11-30');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_event_stats()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        Event::factory()->count(5)->create(['tenant_id' => $tenant->id]);
        Event::factory()->count(2)->upcoming()->create(['tenant_id' => $tenant->id]);
        Event::factory()->count(1)->past()->create(['tenant_id' => $tenant->id]);

        $response = $this->getJson('/api/events/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 8,
                ],
            ]);
    }

    public function test_can_get_upcoming_events()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        Event::factory()->count(3)->upcoming()->create(['tenant_id' => $tenant->id]);
        Event::factory()->count(2)->past()->create(['tenant_id' => $tenant->id]);

        $response = $this->getJson('/api/events/upcoming?limit=10');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_event_with_clients()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        $client1 = Client::factory()->create(['tenant_id' => $tenant->id]);
        $client2 = Client::factory()->create(['tenant_id' => $tenant->id]);

        $payload = [
            'title' => 'Black Friday 2025',
            'type' => 'promocao',
            'start_date' => '2025-11-25 09:00:00',
            'duration_minutes' => 480,
            'location' => 'Loja Física',
            'description' => 'Mega promoção de Black Friday com até 70% de desconto!',
            'client_ids' => [$client1->id, $client2->id],
            'is_active' => true,
        ];

        $response = $this->postJson('/api/events', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Black Friday 2025',
                    'type' => 'promocao',
                ],
            ]);

        $this->assertDatabaseHas('events', [
            'title' => 'Black Friday 2025',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('event_clients', [
            'client_id' => $client1->id,
        ]);
    }

    public function test_cannot_create_event_with_clients_from_other_tenant()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        $otherTenant = Tenant::factory()->create();
        $otherClient = Client::factory()->create(['tenant_id' => $otherTenant->id]);

        $payload = [
            'title' => 'Evento Teste',
            'type' => 'aviso',
            'start_date' => '2025-12-01 10:00:00',
            'duration_minutes' => 60,
            'description' => 'Descrição do evento teste',
            'client_ids' => [$otherClient->id],
        ];

        $response = $this->postJson('/api/events', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_ids']);
    }

    public function test_can_update_event()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        $event = Event::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Título Original',
        ]);

        $payload = [
            'title' => 'Título Atualizado',
            'description' => 'Nova descrição do evento atualizado',
        ];

        $response = $this->putJson("/api/events/{$event->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Título Atualizado',
                ],
            ]);

        $this->assertDatabaseHas('events', [
            'uuid' => $event->uuid,
            'title' => 'Título Atualizado',
        ]);
    }

    public function test_cannot_update_event_from_other_tenant()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        $otherTenant = Tenant::factory()->create();
        $otherEvent = Event::factory()->create(['tenant_id' => $otherTenant->id]);

        $payload = [
            'title' => 'Tentativa de Atualização',
        ];

        $response = $this->putJson("/api/events/{$otherEvent->uuid}", $payload);

        $response->assertStatus(404);
    }

    public function test_can_delete_event()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        $event = Event::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->deleteJson("/api/events/{$event->uuid}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('events', [
            'uuid' => $event->uuid,
        ]);
    }

    public function test_cannot_delete_event_from_other_tenant()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        $otherTenant = Tenant::factory()->create();
        $otherEvent = Event::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->deleteJson("/api/events/{$otherEvent->uuid}");

        $response->assertStatus(404);
    }

    public function test_validation_requires_future_date_for_new_events()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        $client = Client::factory()->create(['tenant_id' => $tenant->id]);

        $payload = [
            'title' => 'Evento Passado',
            'type' => 'aviso',
            'start_date' => '2020-01-01 10:00:00', // Data passada
            'duration_minutes' => 60,
            'description' => 'Descrição do evento',
            'client_ids' => [$client->id],
        ];

        $response = $this->postJson('/api/events', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_validation_requires_all_mandatory_fields()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        $payload = []; // Nenhum campo

        $response = $this->postJson('/api/events', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'type',
                'start_date',
                'duration_minutes',
                'description',
                'client_ids',
            ]);
    }

    public function test_can_create_event_and_send_notifications()
    {
        [$tenant, $_user] = $this->actingTenantUser();

        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'client@example.com',
            'phone' => '11999999999',
        ]);

        $payload = [
            'title' => 'Evento com Notificações',
            'type' => 'promocao',
            'start_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'duration_minutes' => 120,
            'description' => 'Evento teste com envio de notificações',
            'client_ids' => [$client->id],
            'notification_channels' => ['email', 'whatsapp'],
        ];

        $response = $this->postJson('/api/events', $payload);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        // Verificar se cliente foi vinculado
        $this->assertDatabaseHas('event_clients', [
            'client_id' => $client->id,
        ]);
    }
}

