<?php

namespace Tests\Feature\Tenant;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TenantApiControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
            'Accept' => 'application/json',
        ];
    }

    #[Test]
    public function usuario_nao_consegue_atualizar_tenant_de_outra_empresa(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $otherTenantOwner = User::factory()->create(['email_verified_at' => now()]);
        $otherTenant = $otherTenantOwner->tenant;

        $response = $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/tenant/{$otherTenant->uuid}", [
                'name' => 'Nome Invadido',
            ]);

        $response->assertStatus(404);
        $this->assertNotEquals('Nome Invadido', $otherTenant->fresh()->name);
    }

    #[Test]
    public function usuario_atualiza_o_proprio_tenant(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;

        $response = $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/tenant/{$tenant->uuid}", [
                'name' => 'Novo Nome da Empresa',
            ]);

        $response->assertOk();
        $this->assertSame('Novo Nome da Empresa', $tenant->fresh()->name);
    }

    #[Test]
    public function atualizar_settings_faz_merge_em_vez_de_substituir(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = $owner->tenant;
        $tenant->update(['settings' => ['delivery_pickup' => ['pickup_enabled' => true]]]);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->putJson("/api/tenant/{$tenant->uuid}", [
                'settings' => ['require_email_verification' => false],
            ]);

        $response->assertOk();

        $fresh = $tenant->fresh()->settings;
        $this->assertFalse($fresh['require_email_verification']);
        $this->assertTrue($fresh['delivery_pickup']['pickup_enabled']);
    }
}
