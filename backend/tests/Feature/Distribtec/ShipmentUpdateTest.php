<?php

namespace Tests\Feature\Distribtec;

use App\Models\Profile;
use App\Models\Shipment;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

#[Group('distribtec')]
class ShipmentUpdateTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    private function createDraftShipment(array $attrs = []): Shipment
    {
        return Shipment::create(array_merge([
            'tenant_id'     => $this->tenant->id,
            'route_name'    => 'Rota Teste',
            'driver_name'   => 'Motorista Teste',
            'vehicle_plate' => 'TST0001',
            'status'        => 'draft',
            'created_by'    => $this->user->id,
        ], $attrs));
    }

    private function adminHeaders(): array
    {
        $adminProfile = Profile::firstOrCreate(
            ['name' => 'Administrador', 'tenant_id' => $this->tenant->id],
            ['description' => 'Administrador do sistema', 'is_active' => true]
        );

        $adminUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantDistribtecPermissions($adminUser, $this->tenant->id);
        $adminUser->profiles()->attach($adminProfile->id);

        return ['Authorization' => 'Bearer ' . JWTAuth::fromUser($adminUser)];
    }

    // ── Draft shipment ─────────────────────────────────────────────────────────

    #[Test]
    public function regular_user_can_update_draft_shipment(): void
    {
        $shipment = $this->createDraftShipment();

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/shipments/{$shipment->id}", [
                'route_name'    => 'Rota Centro Atualizada',
                'driver_name'   => 'Novo Motorista',
                'vehicle_plate' => 'UPD0001',
                'notes'         => 'Observação atualizada',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.route_name', 'Rota Centro Atualizada')
            ->assertJsonPath('data.driver_name', 'Novo Motorista')
            ->assertJsonPath('data.vehicle_plate', 'UPD0001');

        $this->assertDatabaseHas('shipments', [
            'id'            => $shipment->id,
            'route_name'    => 'Rota Centro Atualizada',
            'driver_name'   => 'Novo Motorista',
            'vehicle_plate' => 'UPD0001',
            'notes'         => 'Observação atualizada',
        ]);
    }

    #[Test]
    public function update_validates_field_lengths(): void
    {
        $shipment = $this->createDraftShipment();

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/shipments/{$shipment->id}", [
                'route_name'    => str_repeat('x', 256),
                'vehicle_plate' => str_repeat('y', 21),
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function update_allows_null_fields(): void
    {
        $shipment = $this->createDraftShipment();

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/shipments/{$shipment->id}", [
                'route_name' => null,
                'notes'      => null,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.route_name', null);
    }

    // ── Dispatched shipment ────────────────────────────────────────────────────

    #[Test]
    public function cannot_update_dispatched_shipment(): void
    {
        $shipment = $this->createDraftShipment(['status' => 'dispatched']);

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/shipments/{$shipment->id}", [
                'route_name' => 'Tentativa de alteração',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Romaneios em trânsito não podem ser editados');
    }

    // ── Delivered shipment ─────────────────────────────────────────────────────

    #[Test]
    public function regular_user_cannot_update_delivered_shipment(): void
    {
        $shipment = $this->createDraftShipment(['status' => 'delivered']);

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/shipments/{$shipment->id}", [
                'route_name' => 'Tentativa não autorizada',
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Apenas administradores podem editar romaneios entregues');
    }

    #[Test]
    public function admin_can_update_delivered_shipment(): void
    {
        $shipment = $this->createDraftShipment(['status' => 'delivered']);

        $this->withHeaders($this->adminHeaders())
            ->putJson("/api/shipments/{$shipment->id}", [
                'route_name' => 'Rota Corrigida pelo Admin',
                'notes'      => 'Correção pós-entrega',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.route_name', 'Rota Corrigida pelo Admin');

        $this->assertDatabaseHas('shipments', [
            'id'         => $shipment->id,
            'route_name' => 'Rota Corrigida pelo Admin',
            'notes'      => 'Correção pós-entrega',
        ]);
    }

    // ── Isolation ──────────────────────────────────────────────────────────────

    #[Test]
    public function cannot_update_shipment_from_another_tenant(): void
    {
        $otherPlan   = \App\Models\Plan::factory()->create();
        $otherTenant = \App\Models\Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);

        $otherShipment = Shipment::create([
            'tenant_id'     => $otherTenant->id,
            'route_name'    => 'Rota de outro tenant',
            'driver_name'   => 'Motorista X',
            'vehicle_plate' => 'OUT0001',
            'status'        => 'draft',
        ]);

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/shipments/{$otherShipment->id}", [
                'route_name' => 'Tentativa cross-tenant',
            ])
            ->assertStatus(404);
    }

    #[Test]
    public function update_returns_404_for_nonexistent_shipment(): void
    {
        $this->withHeaders($this->authHeaders())
            ->putJson('/api/shipments/99999', ['route_name' => 'X'])
            ->assertStatus(404);
    }
}
