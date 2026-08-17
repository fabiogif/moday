<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\TenantBilling;
use App\Models\TenantMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin()
    {
        $admin = AdminUser::factory()->create();
        $token = $admin->createToken('test-token')->plainTextToken;
        
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    #[Test]
    public function it_returns_dashboard_statistics()
    {
        Tenant::factory()->count(10)->create(['account_status' => 'active']);
        Tenant::factory()->count(5)->create(['account_status' => 'trial']);

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tenants' => ['total', 'active', 'trial', 'expired'],
                    'financial' => ['mrr', 'pending_invoices'],
                    'usage' => ['logins_today', 'orders_today'],
                    'growth',
                ],
            ]);
    }

    #[Test]
    public function it_calculates_mrr_correctly()
    {
        Tenant::factory()->create(['account_status' => 'active', 'mrr' => 149]);
        Tenant::factory()->create(['account_status' => 'active', 'mrr' => 299]);
        Tenant::factory()->create(['account_status' => 'trial', 'mrr' => 0]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200);
        
        $mrr = $response->json('data.financial.mrr');
        $this->assertEquals(448, $mrr);
    }

    #[Test]
    public function it_returns_recent_activity()
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/dashboard/activity?limit=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    #[Test]
    public function it_returns_alerts()
    {
        // Cria trials expirando
        Tenant::factory()->count(3)->create([
            'account_status' => 'trial',
            'trial_expires_at' => now()->addDays(2),
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/dashboard/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }
}

