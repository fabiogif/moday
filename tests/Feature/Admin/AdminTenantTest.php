<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminTenantTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(string $role = 'admin')
    {
        $admin = AdminUser::factory()->create(['role' => $role]);
        $token = $admin->createToken('test-token')->plainTextToken;
        
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    #[Test]
    public function it_lists_all_tenants()
    {
        Tenant::factory()->count(15)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/tenants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'subdomain', 'account_status'],
                ],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    #[Test]
    public function it_filters_tenants_by_status()
    {
        Tenant::factory()->count(5)->create(['account_status' => 'active']);
        Tenant::factory()->count(3)->create(['account_status' => 'trial']);

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/tenants?status=active');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(5, $data);
    }

    #[Test]
    public function it_shows_tenant_details()
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAsAdmin()
            ->getJson("/api/admin/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tenant' => ['id', 'name', 'subdomain'],
                    'metrics',
                    'billing',
                    'recent_access',
                    'action_history',
                ],
            ]);
    }

    #[Test]
    public function it_updates_tenant_information()
    {
        $tenant = Tenant::factory()->create(['users_limit' => 5]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/tenants/{$tenant->id}", [
                'users_limit' => 20,
                'messages_limit' => 5000,
                'admin_notes' => 'Cliente VIP',
            ]);

        $response->assertStatus(200);
        
        $tenant->refresh();
        $this->assertEquals(20, $tenant->users_limit);
        $this->assertEquals('Cliente VIP', $tenant->admin_notes);
    }

    #[Test]
    public function it_activates_tenant()
    {
        $tenant = Tenant::factory()->create(['account_status' => 'trial']);

        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/tenants/{$tenant->id}/activate");

        $response->assertStatus(200);
        
        $tenant->refresh();
        $this->assertEquals('active', $tenant->account_status);
        $this->assertNotNull($tenant->activated_at);
    }

    #[Test]
    public function it_suspends_tenant_with_reason()
    {
        $tenant = Tenant::factory()->create(['account_status' => 'active']);

        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/tenants/{$tenant->id}/suspend", [
                'reason' => 'Inadimplência',
            ]);

        $response->assertStatus(200);
        
        $tenant->refresh();
        $this->assertEquals('suspended', $tenant->account_status);
    }

    #[Test]
    public function it_blocks_tenant()
    {
        $tenant = Tenant::factory()->create(['is_blocked' => false]);

        $response = $this->actingAsAdmin('super_admin')
            ->postJson("/api/admin/tenants/{$tenant->id}/block", [
                'reason' => 'Violação de termos',
            ]);

        $response->assertStatus(200);
        
        $tenant->refresh();
        $this->assertTrue($tenant->is_blocked);
        $this->assertEquals('Violação de termos', $tenant->blocked_reason);
    }

    #[Test]
    public function it_logs_admin_actions()
    {
        $tenant = Tenant::factory()->create();

        $this->actingAsAdmin()
            ->postJson("/api/admin/tenants/{$tenant->id}/suspend", [
                'reason' => 'Test',
            ]);

        $this->assertDatabaseHas('admin_action_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'tenant.suspend',
        ]);
    }

    #[Test]
    public function it_shows_registrant_owner_in_tenant_details()
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'dono@empresa.com']);

        $response = $this->actingAsAdmin()
            ->getJson("/api/admin/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.owner.id', $owner->id)
            ->assertJsonPath('data.owner.email', 'dono@empresa.com');
    }

    #[Test]
    public function it_updates_owner_email_and_password()
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'antigo@empresa.com']);

        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/tenants/{$tenant->id}/owner-credentials", [
                'email' => 'novo@empresa.com',
                'password' => 'novaSenha123',
                'password_confirmation' => 'novaSenha123',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'novo@empresa.com');

        $owner->refresh();
        $this->assertEquals('novo@empresa.com', $owner->email);
        $this->assertTrue(Hash::check('novaSenha123', $owner->password));
    }

    #[Test]
    public function it_updates_owner_email_without_changing_password()
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'antigo@empresa.com']);
        $originalPassword = $owner->password;

        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/tenants/{$tenant->id}/owner-credentials", [
                'email' => 'novo@empresa.com',
            ]);

        $response->assertStatus(200);

        $owner->refresh();
        $this->assertEquals('novo@empresa.com', $owner->email);
        $this->assertEquals($originalPassword, $owner->password);
    }

    #[Test]
    public function it_rejects_owner_email_already_used_by_another_user_in_same_tenant()
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'dono@empresa.com']);
        User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'colega@empresa.com']);

        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/tenants/{$tenant->id}/owner-credentials", [
                'email' => 'colega@empresa.com',
            ]);

        $response->assertStatus(422);

        $owner->refresh();
        $this->assertEquals('dono@empresa.com', $owner->email);
    }

    #[Test]
    public function analyst_cannot_update_owner_credentials()
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAsAdmin('analyst')
            ->putJson("/api/admin/tenants/{$tenant->id}/owner-credentials", [
                'email' => 'novo@empresa.com',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function analyst_cannot_modify_tenants()
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAsAdmin('analyst')
            ->putJson("/api/admin/tenants/{$tenant->id}", [
                'users_limit' => 50,
            ]);

        $response->assertStatus(403);
    }
}

